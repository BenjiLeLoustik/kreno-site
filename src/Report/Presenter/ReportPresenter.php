<?php

namespace App\Report\Presenter;

use App\Entity\Booking;
use App\Entity\Report\Report;
use App\Entity\Report\ReportMessage;
use App\Entity\User\User;
use App\Establishment\Helper\CalendarHelper;
use App\Report\Enum\ReportDirection;
use App\Report\Enum\ReportPriority;
use App\Report\Enum\ReportText;
use App\Report\Helper\ReportStatusHelper;
use App\Report\Service\ReportService;
use App\Repository\Report\ReportRepository;
use App\Shared\Helper\DateHelper;
use App\Shared\Helper\DurationHelper;
use App\Shared\Helper\MoneyHelper;
use App\User\Helper\UserNameHelper;
use DateTimeImmutable;

class ReportPresenter
{
    public const HISTORY_DAYS = 30;

    public const STAFF_ROLES = [
        'ROLE_SUPPORT',
        'ROLE_MODERATOR',
        'ROLE_ADMIN'
    ];

    public function __construct(
        private ReportRepository $reports,
        private ReportService $reportService,
    ) {
    }

    public function bookingLabel(?Booking $booking): ?string
    {
        $startsAt = $booking?->getSlot()?->getStartsAt();

        if ($booking === null || $startsAt === null) {
            return null;
        }

        return sprintf(
            '%s · %s · %s',
            $booking->getReference(),
            $booking->getSlot()->getService()?->getName(),
            $this->dateLabel($startsAt),
        );
    }

    public function proBookingLabel(Booking $booking): string
    {
        $date = $this->dateLabel($booking->getSlot()?->getStartsAt()) ?? '';

        return sprintf('%s · %s · %s', $booking->getReference(), $this->shortName($booking->getCustomer()), $date);
    }

    public function base(Report $report): array
    {
        return [
            'id' => $report->getId(),
            'reference' => ReportStatusHelper::reference($report),
            'direction' => $report->getDirection()?->value,
            'reason' => $report->getReason()?->value,
            'reasonLabel' => $report->getReason()?->label(),
            'description' => $report->getDescription(),
            'createdAt' => DateHelper::iso($report->getCreatedAt()),
            'when' => $this->shortDate($report->getCreatedAt()),
            'kind' => ReportStatusHelper::kind($report),
            'open' => ReportStatusHelper::isOpen($report),
            'resolution' => $report->getResolution(),
            'handledAt' => DateHelper::iso($report->getHandledAt()),
            'booking' => $this->booking($report->getBooking()),
            'establishment' => $this->establishment($report),
            'attachment' => $this->attachment($report),
        ];
    }

    public function forCustomer(Report $report): array
    {
        $detail = array_filter([
            $report->getEstablishment()?->getName(),
            $report->getBooking()?->getReference(),
            $report->getResolution() ?? $this->relative($report->getCreatedAt()),
        ]);

        return $this->base($report) + [
                'title' => $report->getReason()?->label(),
                'status' => ReportStatusHelper::customerLabel($report),
                'detail' => implode(' · ', $detail),
                'messages' => $this->messages($report, false),
                'canReply' => ReportStatusHelper::isOpen($report),
            ];
    }

    public function forCustomerList(array $reports): array
    {
        return array_values(array_map(fn (Report $report): array => $this->forCustomer($report), $reports));
    }

    public function forProMine(Report $report): array
    {
        $customer = $this->shortName($report->getReportedUser(), ReportText::CUSTOMER);
        $detail = array_filter([
            $report->getBooking()?->getReference(),
            $this->dateLabel($report->getBooking()?->getSlot()?->getStartsAt()),
            $report->getResolution() ?? $report->getDescription(),
        ]);

        return $this->base($report) + [
                'title' => sprintf('%s · %s', $report->getReason()?->label(), $customer),
                'status' => ReportStatusHelper::proLabel($report),
                'detail' => implode(' · ', $detail),
                'canAnswer' => false,
                'messages' => $this->messages($report, false),
            ];
    }

    public function forProReceived(Report $report): array
    {
        $deadline = $this->reportService->replyDeadline($report);

        return $this->base($report) + [
                'title' => $report->getReason()?->label(),
                'status' => ReportStatusHelper::proLabel($report),
                'detail' => $this->receivedDetail($report),
                'canAnswer' => ReportStatusHelper::isOpen($report),
                'replyDeadline' => DateHelper::iso($deadline),
                'replyRemaining' => $this->remaining($deadline),
                'messages' => $this->messages($report, false),
            ];
    }

    public function forAdmin(Report $report): array
    {
        $similar = $this->similar($report);
        $priority = ReportPriority::of($report->getReason(), $similar);

        return $this->base($report) + [
                'target' => $this->target($report),
                'by' => $this->by($report),
                'date' => $this->relative($report->getCreatedAt()),
                'priority' => $priority->value,
                'priorityLabel' => $priority->label(),
                'status' => ReportStatusHelper::adminLabel($report),
                'similar' => $similar,
            ];
    }

    public function forAdminList(array $reports): array
    {
        return array_values(array_map(fn (Report $report): array => $this->forAdmin($report), $reports));
    }

    public function forAdminDetail(Report $report): array
    {
        $row = $this->forAdmin($report);
        $fromCustomer = $report->getDirection() === ReportDirection::FROM_CUSTOMER;

        return $row + [
                'message' => $report->getDescription(),
                'history' => $this->history($row['similar'], $fromCustomer),
                'reporter' => $this->reporter($report->getReporter()),
                'reportedUser' => $this->reportedUser($report->getReportedUser()),
                'handledBy' => $this->fullName($report->getHandledBy()),
                'messages' => $this->messages($report, true),
                'canDecide' => ReportStatusHelper::isOpen($report),
                'suspendLabel' => $this->suspendLabel($fromCustomer),
            ];
    }

    private function booking(?Booking $booking): ?array
    {
        if ($booking === null) {
            return null;
        }

        return [
            'id' => $booking->getId(),
            'reference' => $booking->getReference(),
            'label' => $this->bookingLabel($booking),
            'price' => MoneyHelper::format($booking->getPrice()),
            'status' => $booking->getStatus()->value,
        ];
    }

    private function establishment(Report $report): ?array
    {
        $establishment = $report->getEstablishment();

        if ($establishment === null) {
            return null;
        }

        return [
            'id' => $establishment->getId(),
            'name' => $establishment->getName(),
            'locality' => $establishment->getLocality(),
        ];
    }

    private function attachment(Report $report): ?array
    {
        $path = $report->getAttachmentPath();

        if ($path === null) {
            return null;
        }

        return [
            'type' => $this->attachmentType($path),
            'name' => $this->reportService->attachmentName($report),
        ];
    }

    private function attachmentType(string $path): string
    {
        if (pathinfo($path, PATHINFO_EXTENSION) === 'pdf') {
            return 'pdf';
        }

        return 'image';
    }

    private function receivedDetail(Report $report): string
    {
        $booking = '';

        if ($report->getBooking() !== null) {
            $booking = ' · ' . $report->getBooking()->getReference();
        }

        $reporter = $this->shortName($report->getReporter(), ReportText::A_CUSTOMER);

        return ReportText::REPORTED_BY->text($reporter, $booking, $report->getDescription());
    }

    private function remaining(?DateTimeImmutable $deadline): ?string
    {
        if ($deadline === null || $deadline <= new DateTimeImmutable()) {
            return null;
        }

        return DurationHelper::remaining($deadline);
    }

    private function target(Report $report): ?string
    {
        if ($report->getDirection() === ReportDirection::FROM_CUSTOMER) {
            return $report->getEstablishment()?->getName();
        }

        return ReportText::CUSTOMER_NAMED->text($this->shortName($report->getReportedUser()));
    }

    private function by(Report $report): ?string
    {
        if ($report->getDirection() === ReportDirection::FROM_CUSTOMER) {
            return $this->shortName($report->getReporter());
        }

        return $report->getEstablishment()?->getName();
    }

    private function history(int $similar, bool $fromCustomer): string
    {
        if ($similar > 1) {
            return ReportText::SIMILAR_MANY->text($similar, self::HISTORY_DAYS);
        }

        if ($similar === 1) {
            return ReportText::SIMILAR_ONE->text($similar, self::HISTORY_DAYS);
        }

        if ($fromCustomer) {
            return ReportText::FIRST_ABOUT_ESTABLISHMENT->text();
        }

        return ReportText::FIRST_ABOUT_CUSTOMER->text();
    }

    private function reporter(?User $reporter): ?array
    {
        if ($reporter === null) {
            return null;
        }

        return [
            'id' => $reporter->getId(),
            'name' => UserNameHelper::fullName($reporter),
            'email' => $reporter->getEmail(),
        ];
    }

    private function reportedUser(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->getId(),
            'name' => UserNameHelper::fullName($user),
            'status' => $user->getStatus()->value,
        ];
    }

    private function suspendLabel(bool $fromCustomer): string
    {
        if ($fromCustomer) {
            return ReportText::SUSPEND_ESTABLISHMENT->text();
        }

        return ReportText::SUSPEND_CUSTOMER->text();
    }

    private function messages(Report $report, bool $withInternal): array
    {
        $messages = [];

        foreach ($report->getMessages() as $message) {
            if (!$message instanceof ReportMessage || ($message->isInternal() && !$withInternal)) {
                continue;
            }

            $messages[] = [
                'id' => $message->getId(),
                'author' => $this->author($report, $message->getAuthor()),
                'body' => $message->getBody(),
                'internal' => $message->isInternal(),
                'createdAt' => DateHelper::iso($message->getCreatedAt()),
            ];
        }

        return $messages;
    }

    private function author(Report $report, ?User $author): string
    {
        if ($author === null) {
            return ReportText::KRENO->text();
        }

        if ($author === $report->getReporter()) {
            return UserNameHelper::shortName($author);
        }

        if ($this->isStaff($author->getRoles())) {
            return ReportText::KRENO_TEAM->text();
        }

        return $report->getEstablishment()?->getName() ?? UserNameHelper::shortName($author);
    }

    private function isStaff(array $roles): bool
    {
        return array_intersect($roles, self::STAFF_ROLES) !== [];
    }

    private function similar(Report $report): int
    {
        $since = ($report->getCreatedAt() ?? new DateTimeImmutable())->modify(sprintf('-%d days', self::HISTORY_DAYS));

        if ($report->getDirection() === ReportDirection::FROM_CUSTOMER) {
            return $this->similarAboutEstablishment($report, $since);
        }

        if ($report->getReportedUser() === null) {
            return 0;
        }

        return $this->reports->countAboutUserSince($report->getReportedUser(), $since, $report);
    }

    private function similarAboutEstablishment(Report $report, DateTimeImmutable $since): int
    {
        if ($report->getEstablishment() === null) {
            return 0;
        }

        return $this->reports->countAboutEstablishmentSince($report->getEstablishment(), $since, $report);
    }

    private function dateLabel(?DateTimeImmutable $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return mb_strtolower(CalendarHelper::shortDate($date)) . ' ' . $date->format('H:i');
    }

    private function shortDate(?DateTimeImmutable $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return mb_strtolower(CalendarHelper::shortDate($date));
    }

    private function relative(?DateTimeImmutable $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return CalendarHelper::relative($date);
    }

    private function shortName(?User $user, ReportText $fallback = ReportText::NONE): string
    {
        if ($user === null) {
            return $fallback->text();
        }

        return UserNameHelper::shortName($user);
    }

    private function fullName(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return UserNameHelper::fullName($user);
    }
}