<?php

namespace App\Booking\Helper;

use App\Booking\Enum\BookingDisplayStatus;
use App\Booking\Enum\BookingStatus;
use App\Entity\Booking;
use DateTimeImmutable;

class BookingStatusHelper
{
    public static function status(Booking $booking, ?DateTimeImmutable $now = null): BookingDisplayStatus
    {
        return match ($booking->getStatus()) {
            BookingStatus::HONORED => BookingDisplayStatus::DONE,
            BookingStatus::NO_SHOW => BookingDisplayStatus::NO_SHOW,
            BookingStatus::CANCELLED_BY_CUSTOMER => BookingDisplayStatus::CANCELLED,
            BookingStatus::CANCELLED_BY_ESTABLISHMENT => BookingDisplayStatus::CANCELLED_BY_ESTABLISHMENT,
            BookingStatus::CONFIRMED => self::confirmedStatus($booking, $now),
        };
    }

    public static function kind(Booking $booking, ?DateTimeImmutable $now = null): string
    {
        return self::status($booking, $now)->value;
    }

    public static function customerLabel(Booking $booking, ?DateTimeImmutable $now = null): string
    {
        return self::status($booking, $now)->customerLabel();
    }

    public static function proLabel(Booking $booking, ?DateTimeImmutable $now = null): string
    {
        return self::status($booking, $now)->proLabel();
    }

    public static function adminLabel(Booking $booking, ?DateTimeImmutable $now = null): string
    {
        return self::status($booking, $now)->adminLabel();
    }

    private static function confirmedStatus(Booking $booking, ?DateTimeImmutable $now): BookingDisplayStatus
    {
        $startsAt = $booking->getSlot()?->getStartsAt();

        if ($startsAt !== null && $startsAt <= ($now ?? new DateTimeImmutable())) {
            return BookingDisplayStatus::PENDING;
        }

        return BookingDisplayStatus::COMING;
    }
}