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
use App\Report\Exception\AppointmentNotPassedException;
use App\Report\Exception\BookingAlreadyReportedException;
use App\Report\Exception\BookingRequiredForReasonException;
use App\Report\Exception\CancelledByEstablishmentReasonException;
use App\Report\Exception\CustomerAlreadyReportedException;
use App\Report\Exception\HonoredBookingNoShowException;
use App\Report\Exception\InvalidAttachmentException;
use App\Report\Exception\MissingAttachmentException;
use App\Report\Exception\NotCancelledByEstablishmentException;
use App\Report\Exception\NotReportOwnerException;
use App\Report\Exception\ReplyNotExpectedException;
use App\Report\Exception\ReportBookingNotFoundException;
use App\Report\Exception\ReportClosedException;
use App\Report\Exception\ReportedBookingCancelledException;
use App\Report\Exception\ReportEstablishmentNotFoundException;
use App\Report\Exception\ReportTargetRequiredException;
use App\Report\Exception\ReportWindowClosedException;
use App\Report\Helper\ReportStatusHelper;
use App\Repository\BookingRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Repository\Report\ReportRepository;
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

    public const ATTACHMENT_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    public const REPORTABLE_BOOKING_STATUSES = [
        BookingStatus::CONFIRMED,
        BookingStatus::HONORED,
        BookingStatus::NO_SHOW,
    ];

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

    public function createFromCustomer(
        User $customer,
        CustomerReportInput $input,
        ?UploadedFile $attachment = null,
    ): Report {
        $reason = ReportReason::from((string) $input->reason);
        [$booking, $establishment] = $this->customerTarget($customer, $input, $reason);

        $report = new Report();
        $report->setReporter($customer);
        $report->setEstablishment($establishment);
        $report->setBooking($booking);
        $report->setDirection(ReportDirection::FROM_CUSTOMER);
        $report->setReason($reason);
        $report->setDescription(trim((string) $input->description));
        $report->setStatus($this->customerReportStatus($booking));

        return $this->save($report, $attachment);
    }

    public function createFromEstablishment(
        CompanyEstablishment $establishment,
        User $author,
        EstablishmentReportInput $input,
    ): Report {
        $reason = ReportReason::from((string) $input->reason);
        $booking = $this->bookings->findOneInEstablishment($establishment, (int) $input->bookingId);

        if ($booking === null) {
            throw new ReportBookingNotFoundException();
        }

        $this->assertEstablishmentCanReport($booking);

        if ($reason === ReportReason::NO_SHOW) {
            $this->declareNoShow($booking);
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
            throw new NotReportOwnerException();
        }

        return $this->post($report, $author, $body);
    }

    public function replyAsEstablishment(Report $report, User $author, string $body): ReportMessage
    {
        if ($report->getDirection() !== ReportDirection::FROM_CUSTOMER) {
            throw new ReplyNotExpectedException();
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
            throw new ReportClosedException();
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
        $limit = ($now ?? new DateTimeImmutable())->modify(sprintf('-%d hours', self::REPLY_WINDOW_HOURS));
        $reports = $this->reports->findAwaitingReplyBefore($limit);

        foreach ($reports as $report) {
            $report->setStatus(ReportStatus::IN_REVIEW);
        }

        $this->orm->flush();

        return count($reports);
    }

    public function canCustomerReport(Booking $booking, ?DateTimeImmutable $now = null): bool
    {
        $reference = $this->reportReference($booking);

        if ($booking->getStatus() === BookingStatus::CANCELLED_BY_CUSTOMER || $reference === null) {
            return false;
        }

        $closesAt = $reference->modify(sprintf('+%d hours', self::CUSTOMER_WINDOW_HOURS));

        if ($closesAt <= ($now ?? new DateTimeImmutable())) {
            return false;
        }

        return !$this->reports->existsForBooking($booking, ReportDirection::FROM_CUSTOMER);
    }

    public function attachment(Report $report): string
    {
        $path = $report->getAttachmentPath();

        if ($path === null || !$this->storage->exists($path)) {
            throw new MissingAttachmentException();
        }

        return $this->storage->read($path);
    }

    public function attachmentName(Report $report): string
    {
        $extension = pathinfo((string) $report->getAttachmentPath(), PATHINFO_EXTENSION);

        return sprintf('%s-piece-jointe.%s', ReportStatusHelper::reference($report), $extension);
    }

    private function customerTarget(User $customer, CustomerReportInput $input, ReportReason $reason): array
    {
        if ($input->booking !== null && $input->booking !== '') {
            $booking = $this->customerBooking($customer, $input->booking, $reason);

            return [
                $booking,
                $booking->getSlot()?->getEstablishment()
            ];
        }

        return [
            null,
            $this->reportableEstablishment($input->establishmentId, $reason)
        ];
    }

    private function customerBooking(User $customer, string $reference, ReportReason $reason): Booking
    {
        $booking = $this->bookings->findOneByReference($reference);

        if (!$booking instanceof Booking || $booking->getCustomer() !== $customer) {
            throw new ReportBookingNotFoundException();
        }

        $this->assertCustomerCanReport($booking, $reason);

        return $booking;
    }

    private function reportableEstablishment(?int $id, ReportReason $reason): CompanyEstablishment
    {
        if ($id === null) {
            throw new ReportTargetRequiredException();
        }

        $establishment = $this->establishments->find($id);

        if (!$establishment instanceof CompanyEstablishment) {
            throw new ReportEstablishmentNotFoundException();
        }

        if ($establishment->getStatus() !== EstablishmentStatus::ONLINE) {
            throw new ReportEstablishmentNotFoundException();
        }

        if ($reason === ReportReason::LAST_MINUTE_CANCELLATION) {
            throw new BookingRequiredForReasonException();
        }

        return $establishment;
    }

    private function customerReportStatus(?Booking $booking): ReportStatus
    {
        if ($booking === null) {
            return ReportStatus::SENT;
        }

        return ReportStatus::AWAITING_REPLY;
    }

    private function assertCustomerCanReport(Booking $booking, ReportReason $reason): void
    {
        if ($this->reports->existsForBooking($booking, ReportDirection::FROM_CUSTOMER)) {
            throw new BookingAlreadyReportedException();
        }

        if (!$this->canCustomerReport($booking)) {
            throw new ReportWindowClosedException(self::CUSTOMER_WINDOW_HOURS);
        }

        $cancelledByEstablishment = $booking->getStatus() === BookingStatus::CANCELLED_BY_ESTABLISHMENT;

        if ($reason === ReportReason::LAST_MINUTE_CANCELLATION && !$cancelledByEstablishment) {
            throw new NotCancelledByEstablishmentException();
        }

        if ($cancelledByEstablishment && !$this->fitsEstablishmentCancellation($reason)) {
            throw new CancelledByEstablishmentReasonException(ReportReason::LAST_MINUTE_CANCELLATION->label());
        }
    }

    private function fitsEstablishmentCancellation(ReportReason $reason): bool
    {
        return $reason === ReportReason::LAST_MINUTE_CANCELLATION || $reason === ReportReason::OTHER;
    }

    private function assertEstablishmentCanReport(Booking $booking): void
    {
        $startsAt = $booking->getSlot()?->getStartsAt();

        if ($startsAt === null || $startsAt > new DateTimeImmutable()) {
            throw new AppointmentNotPassedException();
        }

        if (!in_array($booking->getStatus(), self::REPORTABLE_BOOKING_STATUSES, true)) {
            throw new ReportedBookingCancelledException();
        }

        if ($this->reports->existsForBooking($booking, ReportDirection::FROM_ESTABLISHMENT)) {
            throw new CustomerAlreadyReportedException();
        }
    }

    private function declareNoShow(Booking $booking): void
    {
        if ($booking->getStatus() === BookingStatus::HONORED) {
            throw new HonoredBookingNoShowException();
        }

        if ($booking->getStatus() === BookingStatus::CONFIRMED) {
            $this->attendance->markNoShow($booking);
        }
    }

    private function reportReference(Booking $booking): ?DateTimeImmutable
    {
        if ($booking->getStatus() === BookingStatus::CANCELLED_BY_ESTABLISHMENT) {
            return $booking->getCancelledAt();
        }

        return $booking->getSlot()?->getStartsAt();
    }

    private function save(Report $report, ?UploadedFile $attachment = null): Report
    {
        $this->orm->persist($report);
        $this->orm->flush();

        if ($attachment !== null) {
            $this->storeAttachment($report, $attachment);
        }

        $this->events->dispatch(new ReportCreatedEvent($report));

        return $report;
    }

    private function storeAttachment(Report $report, UploadedFile $attachment): void
    {
        $directory = 'reports/' . date('Y/m');

        try {
            $path = $this->storage->store($attachment, $directory, self::ATTACHMENT_MAX_SIZE, self::ATTACHMENT_TYPES);
            $report->setAttachmentPath($path);
            $this->orm->flush();
        } catch (StorageException) {
            throw new InvalidAttachmentException();
        }
    }
}