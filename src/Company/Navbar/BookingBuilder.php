<?php

namespace App\Company\Navbar;

use App\Company\Security\Permission;
use App\Shared\Helper\Translation;

class BookingBuilder extends AbstractProNavbarBuilder
{
    public function getKey(): string
    {
        return 'bookings';
    }

    public function getLabel(): string
    {
        return Translation::translate('Réservations');
    }

    public function getRoute(): string
    {
        return 'pro_bookings';
    }

    public function getIcon(): string
    {
        return 'calendar-check';
    }

    public function getPosition(): int
    {
        return 50;
    }

    public function getPermission(): ?string
    {
        return Permission::BOOKINGS_MANAGE;
    }
}