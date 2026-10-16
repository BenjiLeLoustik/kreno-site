<?php

namespace App\Booking\Helper;

use App\Booking\Enum\BookingStatus;
use App\Entity\Booking;
use DateTimeImmutable;

class BookingStatusHelper
{
    public const CUSTOMER_LABELS = [
        'coming' => 'Confirmée',
        'done' => 'Honorée',
        'cancelled' => 'Annulée',
        'cancelled_by_establishment' => 'Annulée par l\'établissement',
        'noshow' => 'Absence',
        'pending' => 'En attente de clôture',
    ];

    public const PRO_LABELS = [
        'coming' => 'À venir',
        'done' => 'Honorée',
        'cancelled' => 'Annulée par le client',
        'cancelled_by_establishment' => 'Annulée par vous',
        'noshow' => 'Absent',
        'pending' => 'À clôturer',
    ];

    public const ADMIN_LABELS = [
        'coming' => 'À venir',
        'done' => 'Honorée',
        'cancelled' => 'Annulée',
        'cancelled_by_establishment' => 'Annulée',
        'noshow' => 'Absent',
        'pending' => 'À clôturer',
    ];

    public static function kind(Booking $booking, ?DateTimeImmutable $now = null): string
    {
        return match ($booking->getStatus()) {
            BookingStatus::HONORED => 'done',
            BookingStatus::NO_SHOW => 'noshow',
            BookingStatus::CANCELLED_BY_CUSTOMER => 'cancelled',
            BookingStatus::CANCELLED_BY_ESTABLISHMENT => 'cancelled_by_establishment',
            BookingStatus::CONFIRMED => $booking->getSlot()?->getStartsAt() !== null && $booking->getSlot()->getStartsAt() <= ($now ?? new DateTimeImmutable()) ? 'pending' : 'coming',
        };
    }

    public static function customerLabel(Booking $booking, ?DateTimeImmutable $now = null): string
    {
        return self::CUSTOMER_LABELS[self::kind($booking, $now)];
    }

    public static function proLabel(Booking $booking, ?DateTimeImmutable $now = null): string
    {
        return self::PRO_LABELS[self::kind($booking, $now)];
    }

    public static function adminLabel(Booking $booking, ?DateTimeImmutable $now = null): string
    {
        return self::ADMIN_LABELS[self::kind($booking, $now)];
    }
}