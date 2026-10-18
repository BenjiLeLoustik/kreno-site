<?php

namespace App\Establishment\Presenter;

use App\Company\Enum\CompanyRole;
use App\Company\Helper\SiretHelper;
use App\Company\Presenter\EstablishmentSummaryPresenter;
use App\Entity\Company\Company;
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
                'since' => $this->since($establishment->getVerifiedAt()),
                'suspended' => $establishment->getStatus() === EstablishmentStatus::PAUSED,
            ];
    }

    public function items(array $establishments): array
    {
        return array_values(
            array_map(
                $this->item(...),
                $establishments
            )
        );
    }

    public function detail(CompanyEstablishment $establishment, ?DateTimeImmutable $now = null): array
    {
        $since = ($now ?? new DateTimeImmutable())->modify(sprintf('-%d days', self::STATS_DAYS));
        $company = $establishment->getCompany();
        $siret = $establishment->getSiret();

        return $this->item($establishment) + [
                'company' => $this->company($company),
                'owner' => $this->owner($company),
                'plan' => $this->plan($company),
                'siret' => $siret,
                'siretLabel' => $this->siretLabel($siret),
                'verified' => $establishment->getVerifiedAt() !== null,
                'verifiedAt' => DateHelper::iso($establishment->getVerifiedAt()),
                'stats' => $this->stats($establishment, $since),
                'canSuspend' => $establishment->getStatus() === EstablishmentStatus::ONLINE,
                'canResume' => $establishment->getStatus() === EstablishmentStatus::PAUSED,
            ];
    }

    private function since(?DateTimeImmutable $verifiedAt): ?string
    {
        if ($verifiedAt === null) {
            return null;
        }

        return CalendarHelper::monthYear($verifiedAt);
    }

    private function company(?Company $company): ?array
    {
        if ($company === null) {
            return null;
        }

        return [
            'id' => $company->getId(),
            'name' => $company->getName(),
            'legalName' => $company->getLegalName()
        ];
    }

    private function owner(?Company $company): ?array
    {
        foreach ($company?->getMembers() ?? [] as $member) {
            $user = $member->getUser();

            if ($member->getRole() === CompanyRole::OWNER && $user !== null) {
                return [
                    'name' => UserNameHelper::fullName($user),
                    'email' => $user->getEmail(),
                    'phone' => $user->getPhone(),
                ];
            }
        }

        return null;
    }

    private function plan(?Company $company): ?string
    {
        foreach ($company?->getSubscriptions() ?? [] as $subscription) {
            return $subscription->getPlan()?->getName();
        }

        return null;
    }

    private function siretLabel(?string $siret): ?string
    {
        if ($siret === null) {
            return null;
        }

        return SiretHelper::format($siret);
    }

    private function stats(CompanyEstablishment $establishment, DateTimeImmutable $since): array
    {
        $published = $this->slots->countCreatedSince($establishment, $since);
        $booked = $this->slots->countCreatedSince($establishment, $since, null, SlotStatus::BOOKED);

        return [
            'days' => self::STATS_DAYS,
            'slots' => $published,
            'booked' => $booked,
            'fill' => $this->fillRate($published, $booked),
            'discount' => $this->discount($this->slots->averageDiscountSince($establishment, $since)),
            'followers' => $this->followers->countForEstablishment($establishment),
            'reports' => $this->reports->countAboutEstablishmentSince($establishment, $since),
            'openReports' => $this->reports->countOpenAboutEstablishment($establishment),
        ];
    }

    private function fillRate(int $published, int $booked): string
    {
        if ($published === 0) {
            return '-';
        }

        return sprintf('%d %%', round($booked / $published * 100));
    }

    private function discount(?int $discount): string
    {
        if ($discount === null) {
            return '—';
        }

        return sprintf('-%d %%', $discount);
    }
}