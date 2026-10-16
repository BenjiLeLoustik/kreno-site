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
use App\Establishment\Helper\CalendarHelper;
use App\Establishment\Model\SlotMatch;
use App\Establishment\Service\AlertMatchService;
use App\Mail\Presenter\MailContextPresenter;
use App\Mail\Provider\RecipientProvider;
use App\Mail\Service\MailService;
use App\Shared\Helper\DateHelper;
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
        $establishment = $context['establishment']['name'] ?? '';

        if ($customer !== null) {
            $until = $this->bookings->freeCancellationUntil($booking);

            $this->mail->toUser(MailService::CLIENT, $customer, sprintf('C’est réservé : %s, %s à %s', $establishment, $context['day'], $context['time']), 'booking-confirmed', [
                'booking' => $context,
                'freeCancellationUntil' => $until !== null ? sprintf('%s à %s', CalendarHelper::longDate($until), DateHelper::time($until)) : null,
            ]);
        }

        $this->mail->send(MailService::PRO, $this->recipients->bookingDesk($booking->getSlot()?->getEstablishment()), sprintf('Votre créneau de %s a trouvé preneur', $context['time']), 'pro-booking', [
            'booking' => $context,
            'bookingsUrl' => $this->mail->url('pro_bookings'),
        ]);
    }

    #[AsListener(BookingCancelledEvent::class)]
    public function onCancelled(BookingCancelledEvent $event): void
    {
        $booking = $event->getBooking();
        $context = $this->context->booking($booking);
        $establishment = $context['establishment']['name'] ?? '';

        if ($booking->getStatus() === BookingStatus::CANCELLED_BY_ESTABLISHMENT) {
            $this->notifyCustomerOfEstablishmentCancellation($booking, $context, $establishment);

            return;
        }

        $customer = $booking->getCustomer();

        if ($customer !== null && $booking->getCancellationReason() !== AccountDeletedListener::REASON) {
            $this->mail->toUser(MailService::CLIENT, $customer, sprintf('Ta réservation chez %s est annulée', $establishment), 'booking-cancelled', [
                'booking' => $context,
                'homeUrl' => $this->mail->url('home_index'),
            ]);
        }

        $this->mail->send(MailService::PRO, $this->recipients->bookingDesk($booking->getSlot()?->getEstablishment()), sprintf('Annulation : le rendez-vous de %s, %s, est libéré', $context['time'], $context['day']), 'pro-booking-cancelled', [
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

        $this->mail->toUser(MailService::CLIENT, $customer, sprintf('Dans 1 h : %s à %s', $context['establishment']['name'] ?? '', $context['time']), 'booking-reminder', [
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

        $this->mail->toUser(MailService::SUPPORT, $customer, 'Ton absence à un rendez-vous a été signalée', 'booking-no-show', [
            'booking' => $this->context->booking($booking),
            'suspended' => $event->isCustomerSuspended(),
            'suspendedUntil' => $customer->getSuspendedUntil() !== null ? CalendarHelper::longDate($customer->getSuspendedUntil()) : null,
            'reportsUrl' => $this->mail->url('account_reports'),
            'supportEmail' => $this->supportAddress,
        ]);
    }

    private function notifyCustomerOfEstablishmentCancellation(Booking $booking, array $context, string $establishment): void
    {
        $customer = $booking->getCustomer();

        if ($customer === null) {
            return;
        }

        $alternatives = array_map(fn (SlotMatch $match): array => $this->context->slot($match->getSlot()), $this->alerts->picksFor($customer));

        $this->mail->toUser(MailService::CLIENT, $customer, sprintf('%s a dû annuler ton rendez-vous', $establishment), 'booking-cancelled-by-establishment', [
            'booking' => $context,
            'alternatives' => $alternatives,
            'homeUrl' => $this->mail->url('home_index'),
        ]);
    }
}