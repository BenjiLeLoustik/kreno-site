<?php

namespace App\Report\Presenter;

use App\Entity\Booking;
use App\Entity\Report\Report;
use App\Entity\Report\ReportMessage;
use App\Establishment\Helper\CalendarHelper;
use App\Report\Enum\ReportDirection;
use App\Report\Helper\ReportReasonHelper;
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

    public function __construct(
        private ReportRepository $reports,
        private ReportService $reportService,
    ) {
    }

    public function bookingLabel(?Booking $booking): ?string
    {
        $slot = $booking?->getSlot();

        if ($booking === null || $slot?->getStartsAt() === null) {
            return null;
        }

        $startsAt = $slot->getStartsAt();

        return sprintf('%s · %s · %s %s', $booking->getReference(), $slot->getService()?->getName(), mb_strtolower(CalendarHelper::shortDate($startsAt)), $startsAt->format('H:i'));
    }

    public function proBookingLabel(Booking $booking): string
    {
        $startsAt = $booking->getSlot()?->getStartsAt();

        return sprintf('%s · %s · %s', $booking->getReference(), $booking->getCustomer() !== null ? UserNameHelper::shortName($booking->getCustomer()) : '—', $startsAt !== null ? mb_strtolower(CalendarHelper::shortDate($startsAt)) . ' ' . $startsAt->format('H:i') : '');
    }

    public function base(Report $report): array
    {
        $booking = $report->getBooking();
        $establishment = $report->getEstablishment();

        return [
            'id' => $report->getId(),
            'reference' => ReportStatusHelper::reference($report),
            'direction' => $report->getDirection()?->value,
            'reason' => $report->getReason()?->value,
            'reasonLabel' => $report->getReason()?->label(),
            'description' => $report->getDescription(),
            'createdAt' => DateHelper::iso($report->getCreatedAt()),
            'when' => $report->getCreatedAt() !== null ? mb_strtolower(CalendarHelper::shortDate($report->getCreatedAt())) : null,
            'kind' => ReportStatusHelper::kind($report),
            'open' => ReportStatusHelper::isOpen($report),
            'resolution' => $report->getResolution(),
            'handledAt' => DateHelper::iso($report->getHandledAt()),
            'booking' => $booking === null ? null : [
                'id' => $booking->getId(),
                'reference' => $booking->getReference(),
                'label' => $this->bookingLabel($booking),
                'price' => MoneyHelper::format($booking->getPrice()),
                'status' => $booking->getStatus()->value,
            ],
            'establishment' => $establishment === null ? null : [
                'id' => $establishment->getId(),
                'name' => $establishment->getName(),
                'locality' => $establishment->getLocality(),
            ],
            'attachment' => $report->getAttachmentPath() === null ? null : [
                'type' => pathinfo($report->getAttachmentPath(), PATHINFO_EXTENSION) === 'pdf' ? 'pdf' : 'image',
                'name' => $this->reportService->attachmentName($report),
            ],
        ];
    }

    public function forCustomer(Report $report): array
    {
        $establishment = $report->getEstablishment();

        return $this->base($report) + [
                'title' => $report->getReason()?->label(),
                'status' => ReportStatusHelper::customerLabel($report),
                'detail' => implode(' · ', array_filter([$establishment?->getName(), $report->getBooking()?->getReference(), $report->getResolution() ?? ($report->getCreatedAt() !== null ? CalendarHelper::relative($report->getCreatedAt()) : null)])),
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
        $customer = $report->getReportedUser();
        $startsAt = $report->getBooking()?->getSlot()?->getStartsAt();

        return $this->base($report) + [
                'title' => sprintf('%s · %s', $report->getReason()?->label(), $customer !== null ? UserNameHelper::shortName($customer) : 'client'),
                'status' => ReportStatusHelper::proLabel($report),
                'detail' => implode(' · ', array_filter([
                    $report->getBooking()?->getReference(),
                    $startsAt !== null ? mb_strtolower(CalendarHelper::shortDate($startsAt)) . ' ' . $startsAt->format('H:i') : null,
                    $report->getResolution() ?? $report->getDescription(),
                ])),
                'canAnswer' => false,
                'messages' => $this->messages($report, false),
            ];
    }

    public function forProReceived(Report $report): array
    {
        $reporter = $report->getReporter();
        $deadline = $this->reportService->replyDeadline($report);

        return $this->base($report) + [
                'title' => $report->getReason()?->label(),
                'status' => ReportStatusHelper::proLabel($report),
                'detail' => sprintf('Signalé par %s%s · « %s »', $reporter !== null ? UserNameHelper::shortName($reporter) : 'un client', $report->getBooking() !== null ? ' · ' . $report->getBooking()->getReference() : '', $report->getDescription()),
                'canAnswer' => ReportStatusHelper::isOpen($report),
                'replyDeadline' => DateHelper::iso($deadline),
                'replyRemaining' => $deadline !== null && $deadline > new DateTimeImmutable() ? DurationHelper::remaining($deadline) : null,
                'messages' => $this->messages($report, false),
            ];
    }

    public function forAdmin(Report $report): array
    {
        $similar = $this->similar($report);
        $priority = $report->getReason() !== null ? ReportReasonHelper::priority($report->getReason(), $similar) : 'low';

        return $this->base($report) + [
                'target' => $report->getDirection() === ReportDirection::FROM_CUSTOMER
                    ? $report->getEstablishment()?->getName()
                    : 'client ' . ($report->getReportedUser() !== null ? UserNameHelper::shortName($report->getReportedUser()) : '—'),
                'by' => $report->getDirection() === ReportDirection::FROM_CUSTOMER
                    ? ($report->getReporter() !== null ? UserNameHelper::shortName($report->getReporter()) : '—')
                    : $report->getEstablishment()?->getName(),
                'date' => $report->getCreatedAt() !== null ? CalendarHelper::relative($report->getCreatedAt()) : null,
                'priority' => $priority,
                'priorityLabel' => ReportStatusHelper::PRIORITY_LABELS[$priority],
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
                'history' => match (true) {
                    $row['similar'] === 0 => $fromCustomer ? 'Premier signalement pour cet établissement.' : 'Premier signalement pour ce client.',
                    default => sprintf('%d signalement%s similaire%s ces %d derniers jours.', $row['similar'], $row['similar'] > 1 ? 's' : '', $row['similar'] > 1 ? 's' : '', self::HISTORY_DAYS),
                },
                'reporter' => $report->getReporter() === null ? null : ['id' => $report->getReporter()->getId(), 'name' => UserNameHelper::fullName($report->getReporter()), 'email' => $report->getReporter()->getEmail()],
                'reportedUser' => $report->getReportedUser() === null ? null : ['id' => $report->getReportedUser()->getId(), 'name' => UserNameHelper::fullName($report->getReportedUser()), 'status' => $report->getReportedUser()->getStatus()->value],
                'handledBy' => $report->getHandledBy() !== null ? UserNameHelper::fullName($report->getHandledBy()) : null,
                'messages' => $this->messages($report, true),
                'canDecide' => ReportStatusHelper::isOpen($report),
                'suspendLabel' => $fromCustomer ? 'Suspendre l\'établissement' : 'Suspendre le client 30 jours',
            ];
    }

    private function messages(Report $report, bool $withInternal): array
    {
        $messages = [];

        foreach ($report->getMessages() as $message) {
            if (!$message instanceof ReportMessage || ($message->isInternal() && !$withInternal)) {
                continue;
            }

            $author = $message->getAuthor();
            $messages[] = [
                'id' => $message->getId(),
                'author' => $author === null ? 'Kréno' : ($author === $report->getReporter() ? UserNameHelper::shortName($author) : ($this->isStaff($author->getRoles()) ? 'Équipe Kréno' : ($report->getEstablishment()?->getName() ?? UserNameHelper::shortName($author)))),
                'body' => $message->getBody(),
                'internal' => $message->isInternal(),
                'createdAt' => DateHelper::iso($message->getCreatedAt()),
            ];
        }

        return $messages;
    }

    private function isStaff(array $roles): bool
    {
        return array_intersect($roles, ['ROLE_SUPPORT', 'ROLE_MODERATOR', 'ROLE_ADMIN']) !== [];
    }

    private function similar(Report $report): int
    {
        $since = ($report->getCreatedAt() ?? new DateTimeImmutable())->modify(sprintf('-%d days', self::HISTORY_DAYS));

        if ($report->getDirection() === ReportDirection::FROM_CUSTOMER) {
            return $report->getEstablishment() !== null ? $this->reports->countAboutEstablishmentSince($report->getEstablishment(), $since, $report) : 0;
        }

        return $report->getReportedUser() !== null ? $this->reports->countAboutUserSince($report->getReportedUser(), $since, $report) : 0;
    }
}