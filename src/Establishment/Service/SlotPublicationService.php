<?php

namespace App\Establishment\Service;

use App\Billing\Service\BillingAccessService;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentMember;
use App\Entity\Establishment\EstablishmentService;
use App\Entity\Establishment\EstablishmentSlot;
use App\Entity\User\User;
use App\Establishment\Dto\SlotInput;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Enum\SlotStatus;
use App\Establishment\Event\SlotPublishedEvent;
use App\Establishment\Event\SlotWithdrawnEvent;
use App\Establishment\Exception\DuplicateSlotException;
use App\Establishment\Exception\EstablishmentClosedException;
use App\Establishment\Exception\EstablishmentOfflineException;
use App\Establishment\Exception\InvalidSlotDateException;
use App\Establishment\Exception\RemovalTooLateException;
use App\Establishment\Exception\ServiceNotOfferedException;
use App\Establishment\Exception\SlotDiscountOutOfRangeException;
use App\Establishment\Exception\SlotTooFarException;
use App\Establishment\Exception\SlotTooSoonException;
use App\Establishment\Exception\StaffNotInEstablishmentException;
use App\Establishment\Exception\StaffSlotOverlapException;
use App\Establishment\Exception\WithdrawNotAllowedException;
use App\Repository\Establishment\EstablishmentClosureRepository;
use App\Repository\Establishment\EstablishmentMemberRepository;
use App\Repository\Establishment\EstablishmentServiceRepository;
use App\Repository\Establishment\EstablishmentSlotRepository;
use App\Shared\Helper\MoneyHelper;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class SlotPublicationService
{
    public const REMOVAL_CHOICES = [10, 30, 60];

    public function __construct(
        private OrmManagerInterface $orm,
        private EventManagerInterface $events,
        private WebsiteProvider $website,
        private EstablishmentServiceRepository $services,
        private EstablishmentSlotRepository $slots,
        private EstablishmentClosureRepository $closures,
        private EstablishmentMemberRepository $staff,
        private BillingAccessService $access,
    ) {
    }

    public function defaults(CompanyEstablishment $establishment, ?EstablishmentService $service = null): array
    {
        $rule = $this->website->getRule();
        $choices = $this->removalChoices();

        return [
            'discount' => $this->clampDiscount($this->defaultDiscount($establishment, $service)),
            'minDiscount' => $rule->getMinDiscount(),
            'maxDiscount' => $rule->getMaxDiscount(),
            'removalMinutes' => $choices[0],
            'removalChoices' => $choices,
            'publicationWindowDays' => $rule->getPublicationWindowDays(),
            'bookingLeadMinutes' => $rule->getBookingLeadMinutes(),
        ];
    }

    public function publish(CompanyEstablishment $establishment, User $author, SlotInput $input): EstablishmentSlot
    {
        $this->assertCanPublish($establishment);

        $service = $this->activeService($establishment, $input);
        $defaults = $this->defaults($establishment, $service);
        $startsAt = $this->startsAt($input);
        $visibleUntil = $this->visibleUntil($startsAt, $input->removalMinutes ?? $defaults['removalMinutes']);

        $this->assertWithinWindow($startsAt);

        $discount = $input->discount ?? $defaults['discount'];

        $this->assertDiscount($discount);
        $this->assertOpen($establishment, $service, $startsAt);

        $endsAt = $startsAt->modify(sprintf('+%d minutes', $service->getDuration()));
        $staff = $this->staffMember($establishment, $input->staffId, $startsAt, $endsAt);

        $slot = new EstablishmentSlot();
        $slot->setService($service);
        $slot->setStaff($staff);
        $slot->setPublishedBy($author);
        $slot->setStartsAt($startsAt);
        $slot->setEndsAt($endsAt);
        $slot->setOriginalPrice($service->getPrice());
        $slot->setDiscount($discount);
        $slot->setPrice(MoneyHelper::applyDiscount($service->getPrice(), $discount));
        $slot->setVisibleUntil($visibleUntil);
        $slot->setStatus(SlotStatus::AVAILABLE);

        $establishment->addSlot($slot);
        $this->orm->persist($slot);
        $this->orm->flush();

        $this->events->dispatch(new SlotPublishedEvent($slot));

        return $slot;
    }

    public function withdraw(EstablishmentSlot $slot, ?User $actor = null): EstablishmentSlot
    {
        if ($slot->getStatus() !== SlotStatus::AVAILABLE) {
            throw new WithdrawNotAllowedException();
        }

        $slot->setStatus(SlotStatus::WITHDRAWN);
        $slot->setWithdrawnAt(new DateTimeImmutable());
        $this->orm->flush();

        $this->events->dispatch(new SlotWithdrawnEvent($slot, $actor));

        return $slot;
    }

    public function withdrawAll(CompanyEstablishment $establishment, ?User $actor = null): int
    {
        $now = new DateTimeImmutable();
        $slots = $this->slots->findAllAvailable($establishment);

        foreach ($slots as $slot) {
            $slot->setStatus(SlotStatus::WITHDRAWN);
            $slot->setWithdrawnAt($now);
        }

        $this->orm->flush();

        foreach ($slots as $slot) {
            $this->events->dispatch(new SlotWithdrawnEvent($slot, $actor));
        }

        return count($slots);
    }

    public function expireDue(?DateTimeImmutable $now = null): int
    {
        $slots = $this->slots->findExpirable($now ?? new DateTimeImmutable());

        foreach ($slots as $slot) {
            $slot->setStatus(SlotStatus::EXPIRED);
        }

        $this->orm->flush();

        return count($slots);
    }

    private function removalChoices(): array
    {
        $minimum = $this->website->getRule()->getAutoRemovalMinutes();
        $choices = [];

        foreach (self::REMOVAL_CHOICES as $minutes) {
            if ($minutes >= $minimum) {
                $choices[] = $minutes;
            }
        }

        if ($choices === []) {
            return [$minimum];
        }

        return $choices;
    }

    private function defaultDiscount(CompanyEstablishment $establishment, ?EstablishmentService $service): int
    {
        return $service?->getDefaultDiscount()
            ?? $establishment->getDefaultDiscount()
            ?? $this->website->getRule()->getDefaultDiscount();
    }

    private function assertCanPublish(CompanyEstablishment $establishment): void
    {
        if ($establishment->getStatus() !== EstablishmentStatus::ONLINE) {
            throw new EstablishmentOfflineException();
        }

        if ($establishment->getCompany() !== null) {
            $this->access->assertCanPublish($establishment->getCompany());
        }
    }

    private function activeService(CompanyEstablishment $establishment, SlotInput $input): EstablishmentService
    {
        $service = $this->services->findOneInEstablishment($establishment, (int) $input->serviceId);

        if ($service === null || !$service->isActive()) {
            throw new ServiceNotOfferedException();
        }

        return $service;
    }

    private function startsAt(SlotInput $input): DateTimeImmutable
    {
        $startsAt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $input->day . ' ' . $input->time);

        if ($startsAt === false) {
            throw new InvalidSlotDateException();
        }

        return $startsAt;
    }

    private function visibleUntil(DateTimeImmutable $startsAt, int $removalMinutes): DateTimeImmutable
    {
        $rule = $this->website->getRule();

        if ($removalMinutes < $rule->getAutoRemovalMinutes()) {
            throw new RemovalTooLateException($rule->getAutoRemovalMinutes());
        }

        $visibleUntil = $startsAt->modify(sprintf('-%d minutes', $removalMinutes));
        $bookableFrom = (new DateTimeImmutable())->modify(sprintf('+%d minutes', $rule->getBookingLeadMinutes()));

        if ($visibleUntil <= $bookableFrom) {
            throw new SlotTooSoonException();
        }

        return $visibleUntil;
    }

    private function assertWithinWindow(DateTimeImmutable $startsAt): void
    {
        $days = $this->website->getRule()->getPublicationWindowDays();

        if ($startsAt > (new DateTimeImmutable())->modify(sprintf('+%d days', $days))) {
            throw new SlotTooFarException($days);
        }
    }

    private function assertDiscount(int $discount): void
    {
        $rule = $this->website->getRule();

        if ($discount < $rule->getMinDiscount() || $discount > $rule->getMaxDiscount()) {
            throw new SlotDiscountOutOfRangeException($rule->getMinDiscount(), $rule->getMaxDiscount());
        }
    }

    private function assertOpen(
        CompanyEstablishment $establishment,
        EstablishmentService $service,
        DateTimeImmutable $startsAt,
    ): void {
        if ($this->closures->findCovering($establishment, $startsAt) !== null) {
            throw new EstablishmentClosedException();
        }

        if ($this->slots->findDuplicate($establishment, $service, $startsAt) !== null) {
            throw new DuplicateSlotException();
        }
    }

    private function staffMember(
        CompanyEstablishment $establishment,
        ?int $staffId,
        DateTimeImmutable $startsAt,
        DateTimeImmutable $endsAt,
    ): ?EstablishmentMember {
        if ($staffId === null) {
            return null;
        }

        $staff = $this->staff->findOneInEstablishment($establishment, $staffId);

        if ($staff === null) {
            throw new StaffNotInEstablishmentException();
        }

        if ($this->slots->hasOverlapForStaff($staff, $startsAt, $endsAt)) {
            throw new StaffSlotOverlapException();
        }

        return $staff;
    }

    private function clampDiscount(int $discount): int
    {
        $rule = $this->website->getRule();

        return max($rule->getMinDiscount(), min($rule->getMaxDiscount(), $discount));
    }
}