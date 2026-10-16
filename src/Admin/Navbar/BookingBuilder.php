<?php

namespace App\Admin\Navbar;

class BookingBuilder extends AbstractNavbarBuilder
{
    public function getKey(): string
    {
        return 'bookings';
    }

    public function getLabel(): string
    {
        return 'Réservations';
    }

    public function getRoute(): string
    {
        return 'admin_bookings';
    }

    public function getRole(): string
    {
        return 'ROLE_SUPPORT';
    }

    public function getIcon(): string
    {
        return 'calendar-check';
    }

    public function getPosition(): int
    {
        return 70;
    }
}