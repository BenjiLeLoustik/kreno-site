<?php

namespace App\Booking\Listener;

use App\Booking\Service\BookingService;
use App\User\Event\AccountDeletedEvent;
use NeoPHP\Component\Event\Attribute\AsListener;

class AccountDeletedListener
{
    public const REASON = 'Compte supprimé';

    public function __construct(
        private BookingService $bookings,
    ) {
    }

    #[AsListener(AccountDeletedEvent::class)]
    public function onAccountDeleted(AccountDeletedEvent $event): void
    {
        $this->bookings->cancelAllForCustomer($event->user, self::REASON);
    }
}