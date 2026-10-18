<?php

namespace App\User\Navbar;

use App\Shared\Helper\Translation;

class BookingBuilder extends AbstractAccountNavbarBuilder
{
    public function getKey(): string
    {
        return 'bookings';
    }

    public function getLabel(): string
    {
        return Translation::translate('Mes réservations');
    }

    public function getRoute(): string
    {
        return 'account_bookings';
    }

    public function getIcon(): string
    {
        return 'calendar-check';
    }

    public function getPosition(): int
    {
        return 20;
    }
}