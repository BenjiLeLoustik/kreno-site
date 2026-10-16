<?php

namespace App\Mail\Listener;

use App\Booking\Enum\BookingStatus;
use App\Booking\Event\BookingCancelledEvent;
use App\Booking\Event\BookingConfirmedEvent;
use App\Booking\Event\BookingNoShowEvent;
use App\Booking\Event\BookingReminderEvent;
use App\Booking\Listener\AccountDeletedListener;
use App\Booking\Service\BookingService;
use App\Entity\Booking;
use App\Entity\User\User;
use App\Establishment\Helper\CalendarHelper;
use App\Establishment\Service\AlertMatchService;
use App\Mail\Enum\MailSubject;
use App\Mail\Enum\MailText;
use App\Mail\Presenter\MailContextPresenter;
use App\Mail\Provider\RecipientProvider;
use App\Mail\Service\MailService;
use App\Shared\Helper\DateHelper;
use DateTimeImmutable;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Event\Attribute\AsListener;

class BookingMailListener
{
    public function __construct(
        private MailService $mail,
        private RecipientProvider $recipients,
        private MailContextPresenter $context,
        private BookingService $bookings,
        private AlertMatchService $alerts,
        #[Autowire(config: 'packages.mail.supportAddress')]
        private string $supportAddress = 'aide@kreno.fr',
    ) {
    }

    #[AsListener(BookingConfirmedEvent::class)]
    public function onConfirmed(BookingConfirmedEvent $event): void
    {
        $booking = $event->getBooking();
        $customer = $booking->getCustomer();
        $context = $this->context->booking($booking);

        if ($customer !== null) {
            $subject = MailSubject::BOOKING_CONFIRMED->text(
                $this->establishmentName($context),
                $context['day'],
                $context['time'],
            );

            $this->mail->toUser(MailService::CLIENT, $customer, $subject, 'booking-confirmed', [
                'booking' => $context,
                'freeCancellationUntil' => $this->dateTime($this->bookings->freeCancellationUntil($booking)),
            ]);
        }

        $this->sendToDesk($booking, MailSubject::PRO_BOOKING->text($context['time']), 'pro-booking', [
            'booking' => $context,
            'bookingsUrl' => $this->mail->url('pro_bookings'),
        ]);
    }

    #[AsListener(BookingCancelledEvent::class)]
    public function onCancelled(BookingCancelledEvent $event): void
    {
        $booking = $event->getBooking();
        $context = $this->context->booking($booking);

        if ($booking->getStatus() === BookingStatus::CANCELLED_BY_ESTABLISHMENT) {
            $this->notifyCustomerOfEstablishmentCancellation($booking, $context);

            return;
        }

        $customer = $booking->getCustomer();

        if ($customer !== null && $booking->getCancellationReason() !== AccountDeletedListener::REASON) {
            $subject = MailSubject::BOOKING_CANCELLED->text($this->establishmentName($context));

            $this->mail->toUser(MailService::CLIENT, $customer, $subject, 'booking-cancelled', [
                'booking' => $context,
                'homeUrl' => $this->mail->url('home_index'),
            ]);
        }

        $subject = MailSubject::PRO_BOOKING_CANCELLED->text($context['time'], $context['day']);

        $this->sendToDesk($booking, $subject, 'pro-booking-cancelled', [
            'booking' => $context,
            'slotReleased' => $event->isSlotReleased(),
            'bookingsUrl' => $this->mail->url('pro_bookings'),
            'slotUrl' => $this->mail->url('pro_slot_new'),
        ]);
    }

    #[AsListener(BookingReminderEvent::class)]
    public function onReminder(BookingReminderEvent $event): void
    {
        $booking = $event->getBooking();
        $customer = $booking->getCustomer();

        if ($customer === null) {
            return;
        }

        $context = $this->context->booking($booking);
        $subject = MailSubject::BOOKING_REMINDER->text($this->establishmentName($context), $context['time']);

        $this->mail->toUser(MailService::CLIENT, $customer, $subject, 'booking-reminder', [
            'booking' => $context,
            'canCancel' => $this->bookings->canCustomerCancel($booking),
        ], true);
    }

    #[AsListener(BookingNoShowEvent::class)]
    public function onNoShow(BookingNoShowEvent $event): void
    {
        $booking = $event->getBooking();
        $customer = $booking->getCustomer();

        if ($customer === null) {
            return;
        }

        $this->mail->toUser(MailService::SUPPORT, $customer, MailSubject::BOOKING_NO_SHOW->text(), 'booking-no-show', [
            'booking' => $this->context->booking($booking),
            'suspended' => $event->isCustomerSuspended(),
            'suspendedUntil' => $this->longDate($customer->getSuspendedUntil()),
            'reportsUrl' => $this->mail->url('account_reports'),
            'supportEmail' => $this->supportAddress,
        ]);
    }

    private function notifyCustomerOfEstablishmentCancellation(Booking $booking, array $context): void
    {
        $customer = $booking->getCustomer();

        if ($customer === null) {
            return;
        }

        $subject = MailSubject::BOOKING_CANCELLED_BY_ESTABLISHMENT->text($this->establishmentName($context));

        $this->mail->toUser(MailService::CLIENT, $customer, $subject, 'booking-cancelled-by-establishment', [
            'booking' => $context,
            'alternatives' => $this->alternatives($customer),
            'homeUrl' => $this->mail->url('home_index'),
        ]);
    }

    private function sendToDesk(Booking $booking, string $subject, string $template, array $context): void
    {
        $recipients = $this->recipients->bookingDesk($booking->getSlot()?->getEstablishment());

        $this->mail->send(MailService::PRO, $recipients, $subject, $template, $context);
    }

    private function alternatives(User $customer): array
    {
        $alternatives = [];

        foreach ($this->alerts->picksFor($customer) as $match) {
            $alternatives[] = $this->context->slot($match->getSlot());
        }

        return $alternatives;
    }

    private function establishmentName(array $context): string
    {
        return $context['establishment']['name'] ?? '';
    }

    private function dateTime(?DateTimeImmutable $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return MailText::DATE_AT_TIME->text(CalendarHelper::longDate($date), DateHelper::time($date));
    }

    private function longDate(?DateTimeImmutable $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return CalendarHelper::longDate($date);
    }
}