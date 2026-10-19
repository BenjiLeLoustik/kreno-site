<?php

namespace App\Booking\Service;

use App\Booking\Enum\BookingCalendarText;
use App\Booking\Enum\BookingStatus;
use App\Booking\Exception\BookingNoLongerActiveException;
use App\Booking\Helper\IcsHelper;
use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;

class BookingCalendarService
{
    public const UID_DOMAIN = '@kreno.fr';

    public const CONTENT_TYPE = 'text/calendar; charset=utf-8';

    public function ics(Booking $booking): string
    {
        $slot = $booking->getSlot();

        if ($slot?->getStartsAt() === null || $slot->getEndsAt() === null) {
            throw new BookingNoLongerActiveException();
        }

        if ($booking->getStatus() !== BookingStatus::CONFIRMED) {
            throw new BookingNoLongerActiveException();
        }

        $establishment = $slot->getEstablishment();

        return IcsHelper::event(
            $booking->getReference() . self::UID_DOMAIN,
            $slot->getStartsAt(),
            $slot->getEndsAt(),
            BookingCalendarText::SUMMARY->text($slot->getService()?->getName(), $establishment?->getName()),
            $this->location($establishment),
            BookingCalendarText::DESCRIPTION->text($booking->getReference()),
        );
    }

    public function fileName(Booking $booking): string
    {
        return $booking->getReference() . '.ics';
    }

    private function location(?CompanyEstablishment $establishment): ?string
    {
        if ($establishment === null) {
            return null;
        }

        $address = sprintf(
            '%s, %s %s',
            $establishment->getAddressLine1(),
            $establishment->getPostalCode(),
            $establishment->getLocality(),
        );

        return trim($address, ', ');
    }
}