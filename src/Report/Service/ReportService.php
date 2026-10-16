<?php

namespace App\Report\Service;

use App\Booking\Enum\BookingStatus;
use App\Booking\Service\AttendanceService;
use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Report\Report;
use App\Entity\Report\ReportMessage;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Report\Dto\CustomerReportInput;
use App\Report\Dto\EstablishmentReportInput;
use App\Report\Enum\ReportDirection;
use App\Report\Enum\ReportReason;
use App\Report\Enum\ReportStatus;
use App\Report\Event\ReportCreatedEvent;
use App\Report\Event\ReportMessagePostedEvent;
use App\Report\Helper\ReportStatusHelper;
use App\Repository\BookingRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Repository\Report\ReportRepository;
use App\Shared\Exception\BusinessRuleException;
use App\Shared\Exception\StorageException;
use App\Shared\Storage\PrivateStorage;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Component\Http\Request\UploadedFile;
use NeoPHP\Package\Orm\OrmManagerInterface;

class ReportService
{
    public const CUSTOMER_WINDOW_HOURS = 48;

    public const REPLY_WINDOW_HOURS = 72;

    public const ATTACHMENT_MAX_SIZE = 5242880;

    public const ATTACHMENT_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];

    public function __construct(
        private OrmManagerInterface $orm,
        private EventManagerInterface $events,
        private PrivateStorage $storage,
        private ReportRepository $reports,
        private BookingRepository $bookings,
        private CompanyEstablishmentRepository $establishments,
        private AttendanceService $attendance,
    ) {
    }

    public function createFromCustomer(User $customer, CustomerReportInput $input, ?UploadedFile $attachment = null): Report
    {
        $reason = ReportReason::from((string) $input->reason);
        $booking = null;

        if ($input->booking !== null && $input->booking !== '') {
            $booking = $this->bookings->findOneByReference($input->booking);

            if (!$booking instanceof Booking || $booking->getCustomer() !== $customer) {
                throw new BusinessRuleException('Réservation introuvable.');
            }

            $this->assertCustomerCanReport($booking, $reason);
            $establishment = $booking->getSlot()?->getEstablishment();
        } elseif ($input->establishmentId !== null) {
            $establishment = $this->establishments->find($input->establishmentId);

            if (!$establishment instanceof CompanyEstablishment || $establishment->getStatus() !== EstablishmentStatus::ONLINE) {
                throw new BusinessRuleException('Établissement introuvable.');
            }

            if ($reason === ReportReason::LAST_MINUTE_CANCELLATION) {
                throw new BusinessRuleException('Ce motif concerne une réservation : choisis-la.');
            }
        } else {
            throw new BusinessRuleException('Choisis la réservation ou l\'établissement concerné.');
        }

        $report = new Report();
        $report->setReporter($customer);
        $report->setEstablishment($establishment);
        $report->setBooking($booking);
        $report->setDirection(ReportDirection::FROM_CUSTOMER);
        $report->setReason($reason);
        $report->setDescription(trim((string) $input->description));
        $report->setStatus($booking !== null ? ReportStatus::AWAITING_REPLY : ReportStatus::SENT);

        return $this->save($report, $attachment);
    }

    public function createFromEstablishment(CompanyEstablishment $establishment, User $author, EstablishmentReportInput $input): Report
    {
        $reason = ReportReason::from((string) $input->reason);
        $booking = $this->bookings->findOneInEstablishment($establishment, (int) $input->bookingId) ?? throw new BusinessRuleException('Réservation introuvable.');
        $startsAt = $booking->getSlot()?->getStartsAt();

        if ($startsAt === null || $startsAt > new DateTimeImmutable()) {
            throw new BusinessRuleException('Vous pourrez signaler ce client une fois l\'heure du rendez-vous passée.');
        }

        if (!in_array($booking->getStatus(), [BookingStatus::CONFIRMED, BookingStatus::HONORED, BookingStatus::NO_SHOW], true)) {
            throw new BusinessRuleException('Cette réservation a été annulée.');
        }

        if ($this->reports->existsForBooking($booking, ReportDirection::FROM_ESTABLISHMENT)) {
            throw new BusinessRuleException('Ce client a déjà été signalé pour cette réservation.');
        }

        if ($reason === ReportReason::NO_SHOW) {
            if ($booking->getStatus() === BookingStatus::HONORED) {
                throw new BusinessRuleException('Ce rendez-vous a été marqué comme honoré : il ne peut plus être déclaré en absence.');
            }

            if ($booking->getStatus() === BookingStatus::CONFIRMED) {
                $this->attendance->markNoShow($booking);
            }
        }

        $report = new Report();
        $report->setReporter($author);
        $report->setEstablishment($establishment);
        $report->setBooking($booking);
        $report->setReportedUser($booking->getCustomer());
        $report->setDirection(ReportDirection::FROM_ESTABLISHMENT);
        $report->setReason($reason);
        $report->setDescription(trim((string) $input->description));
        $report->setStatus(ReportStatus::SENT);

        return $this->save($report);
    }

    public function replyAsReporter(Report $report, User $author, string $body): ReportMessage
    {
        if ($report->getReporter() !== $author) {
            throw new BusinessRuleException('Ce signalement ne vous appartient pas.');
        }

        return $this->post($report, $author, $body);
    }

    public function replyAsEstablishment(Report $report, User $author, string $body): ReportMessage
    {
        if ($report->getDirection() !== ReportDirection::FROM_CUSTOMER) {
            throw new BusinessRuleException('Ce signalement n\'attend pas de réponse de votre part.');
        }

        $message = $this->post($report, $author, $body, false);

        if ($report->getStatus() === ReportStatus::AWAITING_REPLY) {
            $report->setStatus(ReportStatus::IN_REVIEW);
            $this->orm->flush();
        }

        return $message;
    }

    public function post(Report $report, ?User $author, string $body, bool $internal = false): ReportMessage
    {
        if (!$internal && !ReportStatusHelper::isOpen($report)) {
            throw new BusinessRuleException('Ce signalement est clos.');
        }

        $message = new ReportMessage();
        $message->setAuthor($author);
        $message->setBody(trim($body));
        $message->setInternal($internal);
        $report->addMessage($message);
        $this->orm->persist($message);
        $this->orm->flush();

        if (!$internal) {
            $this->events->dispatch(new ReportMessagePostedEvent($message));
        }

        return $message;
    }

    public function replyDeadline(Report $report): ?DateTimeImmutable
    {
        return $report->getCreatedAt()?->modify(sprintf('+%d hours', self::REPLY_WINDOW_HOURS));
    }

    public function escalateUnanswered(?DateTimeImmutable $now = null): int
    {
        $reports = $this->reports->findAwaitingReplyBefore(($now ?? new DateTimeImmutable())->modify(sprintf('-%d hours', self::REPLY_WINDOW_HOURS)));

        foreach ($reports as $report) {
            $report->setStatus(ReportStatus::IN_REVIEW);
        }

        $this->orm->flush();

        return count($reports);
    }

    public function canCustomerReport(Booking $booking, ?DateTimeImmutable $now = null): bool
    {
        $reference = $booking->getStatus() === BookingStatus::CANCELLED_BY_ESTABLISHMENT ? $booking->getCancelledAt() : $booking->getSlot()?->getStartsAt();

        return $booking->getStatus() !== BookingStatus::CANCELLED_BY_CUSTOMER
            && $reference !== null
            && $reference->modify(sprintf('+%d hours', self::CUSTOMER_WINDOW_HOURS)) > ($now ?? new DateTimeImmutable())
            && !$this->reports->existsForBooking($booking, ReportDirection::FROM_CUSTOMER);
    }

    public function attachment(Report $report): string
    {
        if ($report->getAttachmentPath() === null || !$this->storage->exists($report->getAttachmentPath())) {
            throw new BusinessRuleException('Ce signalement n\'a pas de pièce jointe.');
        }

        return $this->storage->read($report->getAttachmentPath());
    }

    public function attachmentName(Report $report): string
    {
        return sprintf('%s-piece-jointe.%s', ReportStatusHelper::reference($report), pathinfo((string) $report->getAttachmentPath(), PATHINFO_EXTENSION));
    }

    private function assertCustomerCanReport(Booking $booking, ReportReason $reason): void
    {
        if ($this->reports->existsForBooking($booking, ReportDirection::FROM_CUSTOMER)) {
            throw new BusinessRuleException('Tu as déjà signalé un problème sur cette réservation : ajoute un message à ton signalement.');
        }

        if (!$this->canCustomerReport($booking)) {
            throw new BusinessRuleException('Le signalement est possible dans les {hours} h qui suivent le rendez-vous.', ['hours' => self::CUSTOMER_WINDOW_HOURS]);
        }

        if ($reason === ReportReason::LAST_MINUTE_CANCELLATION && $booking->getStatus() !== BookingStatus::CANCELLED_BY_ESTABLISHMENT) {
            throw new BusinessRuleException('Cette réservation n\'a pas été annulée par l\'établissement.');
        }

        if ($booking->getStatus() === BookingStatus::CANCELLED_BY_ESTABLISHMENT && $reason !== ReportReason::LAST_MINUTE_CANCELLATION && $reason !== ReportReason::OTHER) {
            throw new BusinessRuleException('Cette réservation a été annulée par l\'établissement : choisis « {reason} ».', ['reason' => ReportReason::LAST_MINUTE_CANCELLATION->label()]);
        }
    }

    private function save(Report $report, ?UploadedFile $attachment = null): Report
    {
        $this->orm->persist($report);
        $this->orm->flush();

        if ($attachment !== null) {
            try {
                $report->setAttachmentPath($this->storage->store($attachment, 'reports/' . date('Y/m'), self::ATTACHMENT_MAX_SIZE, self::ATTACHMENT_TYPES));
                $this->orm->flush();
            } catch (StorageException) {
                throw new BusinessRuleException('La pièce jointe doit être une image ou un PDF de moins de 5 Mo.');
            }
        }

        $this->events->dispatch(new ReportCreatedEvent($report));

        return $report;
    }
}