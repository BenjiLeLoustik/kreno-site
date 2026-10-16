<?php

namespace App\Establishment\Service;

use App\Billing\Service\BillingAccessService;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentService;
use App\Entity\Establishment\EstablishmentSlot;
use App\Entity\User\User;
use App\Establishment\Dto\SlotInput;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Enum\SlotStatus;
use App\Establishment\Event\SlotPublishedEvent;
use App\Establishment\Event\SlotWithdrawnEvent;
use App\Repository\Establishment\EstablishmentClosureRepository;
use App\Repository\Establishment\EstablishmentMemberRepository;
use App\Repository\Establishment\EstablishmentServiceRepository;
use App\Repository\Establishment\EstablishmentSlotRepository;
use App\Shared\Exception\BusinessRuleException;
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
        $choices = array_values(array_filter(self::REMOVAL_CHOICES, static fn (int $minutes): bool => $minutes >= $rule->getAutoRemovalMinutes()));

        if ($choices === []) {
            $choices = [$rule->getAutoRemovalMinutes()];
        }

        return [
            'discount' => $this->clampDiscount($service?->getDefaultDiscount() ?? $establishment->getDefaultDiscount() ?? $rule->getDefaultDiscount()),
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
        if ($establishment->getStatus() !== EstablishmentStatus::ONLINE) {
            throw new BusinessRuleException('L\'établissement doit être en ligne pour publier un créneau.');
        }

        if ($establishment->getCompany() !== null) {
            $this->access->assertCanPublish($establishment->getCompany());
        }

        $service = $this->services->findOneInEstablishment($establishment, (int) $input->serviceId);

        if ($service === null || !$service->isActive()) {
            throw new BusinessRuleException('Cette prestation n\'existe pas ou n\'est plus proposée.');
        }

        $rule = $this->website->getRule();
        $now = new DateTimeImmutable();
        $startsAt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $input->day . ' ' . $input->time);

        if ($startsAt === false) {
            throw new BusinessRuleException('Le jour ou l\'heure du créneau est invalide.');
        }

        $removalMinutes = $input->removalMinutes ?? $this->defaults($establishment, $service)['removalMinutes'];

        if ($removalMinutes < $rule->getAutoRemovalMinutes()) {
            throw new BusinessRuleException('Un créneau est retiré au plus tard {minutes} min avant son début.', ['minutes' => $rule->getAutoRemovalMinutes()]);
        }

        $visibleUntil = $startsAt->modify(sprintf('-%d minutes', $removalMinutes));

        if ($visibleUntil <= $now->modify(sprintf('+%d minutes', $rule->getBookingLeadMinutes()))) {
            throw new BusinessRuleException('Ce créneau commence trop tôt : il serait retiré avant de pouvoir être réservé.');
        }

        if ($startsAt > $now->modify(sprintf('+%d days', $rule->getPublicationWindowDays()))) {
            throw new BusinessRuleException('Un créneau se publie au plus {days} jours à l\'avance.', ['days' => $rule->getPublicationWindowDays()]);
        }

        $discount = $input->discount ?? $this->defaults($establishment, $service)['discount'];

        if ($discount < $rule->getMinDiscount() || $discount > $rule->getMaxDiscount()) {
            throw new BusinessRuleException('La remise doit être comprise entre {min} % et {max} %.', ['min' => $rule->getMinDiscount(), 'max' => $rule->getMaxDiscount()]);
        }

        if ($this->closures->findCovering($establishment, $startsAt) !== null) {
            throw new BusinessRuleException('L\'établissement est fermé ce jour-là.');
        }

        if ($this->slots->findDuplicate($establishment, $service, $startsAt) !== null) {
            throw new BusinessRuleException('Ce créneau est déjà publié.');
        }

        $endsAt = $startsAt->modify(sprintf('+%d minutes', $service->getDuration()));
        $staff = null;

        if ($input->staffId !== null) {
            $staff = $this->staff->findOneInEstablishment($establishment, $input->staffId) ?? throw new BusinessRuleException('Ce membre ne fait pas partie de l\'établissement.');

            if ($this->slots->hasOverlapForStaff($staff, $startsAt, $endsAt)) {
                throw new BusinessRuleException('Ce membre a déjà un créneau sur cet horaire.');
            }
        }

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
            throw new BusinessRuleException('Seul un créneau en ligne peut être retiré.');
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

    private function clampDiscount(int $discount): int
    {
        $rule = $this->website->getRule();

        return max($rule->getMinDiscount(), min($rule->getMaxDiscount(), $discount));
    }
}