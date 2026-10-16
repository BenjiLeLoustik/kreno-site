<?php

namespace App\Establishment\Presenter;

use App\Company\Enum\CompanyRole;
use App\Company\Helper\SiretHelper;
use App\Company\Presenter\EstablishmentSummaryPresenter;
use App\Entity\Company\CompanyEstablishment;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Enum\SlotStatus;
use App\Establishment\Helper\CalendarHelper;
use App\Repository\Establishment\EstablishmentFollowerRepository;
use App\Repository\Establishment\EstablishmentSlotRepository;
use App\Repository\Report\ReportRepository;
use App\Shared\Helper\DateHelper;
use App\User\Helper\UserNameHelper;
use DateTimeImmutable;

class AdminEstablishmentPresenter
{
    public const STATS_DAYS = 30;

    public function __construct(
        private EstablishmentSummaryPresenter $summaryPresenter,
        private EstablishmentSlotRepository $slots,
        private EstablishmentFollowerRepository $followers,
        private ReportRepository $reports,
    ) {
    }

    public function item(CompanyEstablishment $establishment): array
    {
        return $this->summaryPresenter->summary($establishment) + [
                'activity' => $establishment->getCategory()?->getName(),
                'since' => $establishment->getVerifiedAt() !== null ? CalendarHelper::monthYear($establishment->getVerifiedAt()) : null,
                'suspended' => $establishment->getStatus() === EstablishmentStatus::PAUSED,
            ];
    }

    public function items(array $establishments): array
    {
        return array_values(array_map($this->item(...), $establishments));
    }

    public function detail(CompanyEstablishment $establishment, ?DateTimeImmutable $now = null): array
    {
        $since = ($now ?? new DateTimeImmutable())->modify(sprintf('-%d days', self::STATS_DAYS));
        $published = $this->slots->countCreatedSince($establishment, $since);
        $booked = $this->slots->countCreatedSince($establishment, $since, null, SlotStatus::BOOKED);
        $discount = $this->slots->averageDiscountSince($establishment, $since);
        $company = $establishment->getCompany();
        $owner = null;
        $plan = null;

        foreach ($company?->getMembers() ?? [] as $member) {
            if ($member->getRole() === CompanyRole::OWNER && $member->getUser() !== null) {
                $owner = ['name' => UserNameHelper::fullName($member->getUser()), 'email' => $member->getUser()->getEmail(), 'phone' => $member->getUser()->getPhone()];
                break;
            }
        }

        foreach ($company?->getSubscriptions() ?? [] as $subscription) {
            $plan = $subscription->getPlan()?->getName();
            break;
        }

        return $this->item($establishment) + [
                'company' => $company === null ? null : ['id' => $company->getId(), 'name' => $company->getName(), 'legalName' => $company->getLegalName()],
                'owner' => $owner,
                'plan' => $plan,
                'siret' => $establishment->getSiret(),
                'siretLabel' => $establishment->getSiret() !== null ? SiretHelper::format($establishment->getSiret()) : null,
                'verified' => $establishment->getVerifiedAt() !== null,
                'verifiedAt' => DateHelper::iso($establishment->getVerifiedAt()),
                'stats' => [
                    'days' => self::STATS_DAYS,
                    'slots' => $published,
                    'booked' => $booked,
                    'fill' => $published > 0 ? sprintf('%d %%', round($booked / $published * 100)) : '—',
                    'discount' => $discount !== null ? sprintf('-%d %%', $discount) : '—',
                    'followers' => $this->followers->countForEstablishment($establishment),
                    'reports' => $this->reports->countAboutEstablishmentSince($establishment, $since),
                    'openReports' => $this->reports->countOpenAboutEstablishment($establishment),
                ],
                'canSuspend' => $establishment->getStatus() === EstablishmentStatus::ONLINE,
                'canResume' => $establishment->getStatus() === EstablishmentStatus::PAUSED,
            ];
    }
}