<?php

namespace App\Establishment\Provider;

use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\Establishment\EstablishmentMember;
use App\Entity\Establishment\EstablishmentSlot;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Enum\SlotFilter;
use App\Establishment\Exception\ProSlotNotFoundException;
use App\Establishment\Helper\CalendarHelper;
use App\Establishment\Presenter\ServicePresenter;
use App\Establishment\Presenter\SlotPresenter;
use App\Establishment\Service\SlotPublicationService;
use App\Repository\Establishment\EstablishmentMemberRepository;
use App\Repository\Establishment\EstablishmentServiceRepository;
use App\Repository\Establishment\EstablishmentSlotRepository;
use App\Shared\Helper\DateHelper;
use App\User\Helper\UserNameHelper;
use DateTimeImmutable;

class ProSlotPageProvider
{
    public const HISTORY_DAYS = 7;

    public function __construct(
        private PermissionResolver $resolver,
        private SlotPublicationService $publication,
        private EstablishmentSlotRepository $slots,
        private EstablishmentServiceRepository $services,
        private EstablishmentMemberRepository $staff,
        private SlotPresenter $slotPresenter,
        private ServicePresenter $servicePresenter,
    ) {
    }

    public function index(CompanyMember $member, CompanyEstablishment $establishment, SlotFilter $filter): array
    {
        $now = new DateTimeImmutable();
        $since = $now->setTime(0, 0)->modify(sprintf('-%d days', self::HISTORY_DAYS));

        return [
            'filter' => $filter->value,
            'tabs' => $this->tabs($establishment, $since),
            'rows' => $this->rows($establishment, $filter, $since, $now),
            'historyDays' => self::HISTORY_DAYS,
            'canPublish' => $this->resolver->memberCan($member, Permission::SLOTS_MANAGE, $establishment),
        ];
    }

    public function form(CompanyEstablishment $establishment, int $sourceId, ?int $serviceId, ?string $day): array
    {
        $now = new DateTimeImmutable();
        $source = $this->source($establishment, $sourceId);
        $defaults = $this->publication->defaults($establishment, $source?->getService());

        return [
            'establishment' => [
                'name' => $establishment->getName(),
                'locality' => $establishment->getLocality(),
                'online' => $establishment->getStatus() === EstablishmentStatus::ONLINE,
                'statusLabel' => $establishment->getStatus()->label(),
            ],
            'services' => $this->servicePresenter->summaries($this->services->findActive($establishment)),
            'staff' => $this->staffOptions($establishment),
            'days' => $this->days($now, $defaults['publicationWindowDays']),
            'defaults' => $defaults,
            'prefill' => [
                'serviceId' => $source?->getService()?->getId() ?? $serviceId,
                'day' => $day ?? $now->format('Y-m-d'),
                'time' => $this->time($source, $now),
                'discount' => $source?->getDiscount() ?? $defaults['discount'],
                'staffId' => $source?->getStaff()?->getId(),
            ],
        ];
    }

    public function owned(CompanyEstablishment $establishment, int $id): EstablishmentSlot
    {
        $slot = $this->slots->findOneInEstablishment($establishment, $id);

        if ($slot === null) {
            throw new ProSlotNotFoundException();
        }

        return $slot;
    }

    private function tabs(CompanyEstablishment $establishment, DateTimeImmutable $since): array
    {
        $tabs = [];

        foreach (SlotFilter::cases() as $filter) {
            $tabs[] = [
                'key' => $filter->value,
                'label' => $filter->label(),
                'count' => $this->slots->countForPro($establishment, $filter->statuses(), $since),
            ];
        }

        return $tabs;
    }

    private function rows(
        CompanyEstablishment $establishment,
        SlotFilter $filter,
        DateTimeImmutable $since,
        DateTimeImmutable $now,
    ): array {
        $rows = [];

        foreach ($this->slots->findForPro($establishment, $filter->statuses(), $since) as $slot) {
            $rows[] = $this->slotPresenter->forPro($slot, $now);
        }

        return $rows;
    }

    private function source(CompanyEstablishment $establishment, int $sourceId): ?EstablishmentSlot
    {
        if ($sourceId <= 0) {
            return null;
        }

        return $this->slots->findOneInEstablishment($establishment, $sourceId);
    }

    private function staffOptions(CompanyEstablishment $establishment): array
    {
        $staff = [];

        foreach ($this->staff->findForEstablishment($establishment) as $member) {
            $staff[] = [
                'id' => $member->getId(),
                'name' => $this->staffName($member),
                'role' => $member->getRole()->label(),
            ];
        }

        return $staff;
    }

    private function staffName(EstablishmentMember $member): ?string
    {
        $user = $member->getMember()?->getUser();

        if ($user === null) {
            return null;
        }

        return UserNameHelper::fullName($user);
    }

    private function days(DateTimeImmutable $now, int $windowDays): array
    {
        $days = [];

        for ($offset = 0; $offset <= $windowDays; ++$offset) {
            $day = $now->setTime(0, 0)->modify(sprintf('+%d days', $offset));
            $days[] = ['value' => $day->format('Y-m-d'), 'label' => CalendarHelper::dayLabel($day, $now)];
        }

        return $days;
    }

    private function time(?EstablishmentSlot $source, DateTimeImmutable $now): ?string
    {
        if ($source === null) {
            return $now->modify('+1 hour')->format('H:00');
        }

        return DateHelper::time($source->getStartsAt());
    }
}