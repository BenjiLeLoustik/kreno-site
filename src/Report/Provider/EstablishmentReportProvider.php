<?php

namespace App\Report\Provider;

use App\Booking\Enum\BookingStatus;
use App\Company\Exception\RoleNotAllowedException;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\Report\Report;
use App\Report\Enum\ProReportTab;
use App\Report\Enum\ProReportText;
use App\Report\Enum\ReportDirection;
use App\Report\Enum\ReportReason;
use App\Report\Exception\EstablishmentReportNotFoundException;
use App\Report\Helper\ReportReasonHelper;
use App\Report\Presenter\ReportPresenter;
use App\Repository\BookingRepository;
use App\Repository\Report\ReportRepository;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;

class EstablishmentReportProvider
{
    public const BOOKING_STATUSES = [BookingStatus::CONFIRMED, BookingStatus::HONORED, BookingStatus::NO_SHOW];

    public function __construct(
        private PermissionResolver $resolver,
        private ReportRepository $reports,
        private BookingRepository $bookings,
        private WebsiteProvider $website,
        private ReportPresenter $presenter,
    ) {
    }

    public function index(
        CompanyMember $member,
        CompanyEstablishment $establishment,
        string $tab,
        ?string $reason,
        ?int $bookingId,
    ): array {
        $canReport = $this->resolver->memberCan($member, Permission::BOOKINGS_MANAGE, $establishment);
        $canSeeReceived = $this->resolver->memberCan($member, Permission::ESTABLISHMENT_EDIT, $establishment);

        if (!$canReport && !$canSeeReceived) {
            throw new RoleNotAllowedException();
        }

        $selected = ProReportTab::resolve($tab, $canReport, $canSeeReceived);
        $mine = $this->list($canReport, $establishment, ReportDirection::FROM_ESTABLISHMENT);
        $received = $this->list($canSeeReceived, $establishment, ReportDirection::FROM_CUSTOMER);

        return [
            'tab' => $selected->value,
            'tabs' => $this->tabs($canReport, count($mine), $canSeeReceived, count($received)),
            'rows' => $this->rows($selected, $mine, $received),
            'form' => $this->form($canReport, $establishment, $reason, $bookingId),
        ];
    }

    public function show(Report $report): array
    {
        $received = $report->getDirection() === ReportDirection::FROM_CUSTOMER;

        return [
            'received' => $received,
            'report' => $this->present($report, $received),
        ];
    }

    public function owned(CompanyEstablishment $establishment, int $id): Report
    {
        $report = $this->reports->findOneForEstablishment($establishment, $id);

        if ($report === null) {
            throw new EstablishmentReportNotFoundException();
        }

        return $report;
    }

    public function permission(Report $report): string
    {
        if ($report->getDirection() === ReportDirection::FROM_CUSTOMER) {
            return Permission::ESTABLISHMENT_EDIT;
        }

        return Permission::BOOKINGS_MANAGE;
    }

    private function list(bool $allowed, CompanyEstablishment $establishment, ReportDirection $direction): array
    {
        if (!$allowed) {
            return [];
        }

        return $this->reports->findByEstablishment($establishment, $direction);
    }

    private function tabs(bool $canReport, int $mine, bool $canSeeReceived, int $received): array
    {
        $tabs = [];

        if ($canReport) {
            $tabs[] = [
                'key' => ProReportTab::MINE->value,
                'label' => ProReportTab::MINE->label(),
                'count' => $mine
            ];
        }

        if ($canSeeReceived) {
            $tabs[] = [
                'key' => ProReportTab::RECEIVED->value,
                'label' => ProReportTab::RECEIVED->label(),
                'count' => $received,
            ];
        }

        return $tabs;
    }

    private function rows(ProReportTab $tab, array $mine, array $received): array
    {
        $rows = [];

        if ($tab === ProReportTab::MINE) {
            foreach ($mine as $report) {
                $rows[] = $this->presenter->forProMine($report);
            }

            return $rows;
        }

        foreach ($received as $report) {
            $rows[] = $this->presenter->forProReceived($report);
        }

        return $rows;
    }

    private function form(
        bool $canReport,
        CompanyEstablishment $establishment,
        ?string $reason,
        ?int $bookingId,
    ): ?array {
        if (!$canReport) {
            return null;
        }

        $rule = $this->website->getRule();
        $selectedReason = $reason ?? ReportReason::NO_SHOW->value;

        return [
            'bookings' => $this->bookingOptions($establishment),
            'reasons' => ReportReasonHelper::choices(ReportDirection::FROM_ESTABLISHMENT, $selectedReason),
            'bookingId' => $bookingId,
            'notice' => ProReportText::NO_SHOW_NOTICE->text($rule->getToleratedNoShows(), $rule->getNoShowWindowDays()),
        ];
    }

    private function bookingOptions(CompanyEstablishment $establishment): array
    {
        $options = [];
        $bookings = $this->bookings->findForEstablishment(
            $establishment,
            self::BOOKING_STATUSES,
            null,
            new DateTimeImmutable(),
        );

        foreach ($bookings as $booking) {
            $options[] = [
                'id' => $booking->getId(),
                'label' => $this->presenter->proBookingLabel($booking)
            ];
        }

        return $options;
    }

    private function present(Report $report, bool $received): array
    {
        if ($received) {
            return $this->presenter->forProReceived($report);
        }

        return $this->presenter->forProMine($report);
    }
}