<?php

namespace App\Booking\Enum;

use App\Shared\Helper\Translation;
use DateTimeImmutable;

enum ProBookingTab: string
{
    case UPCOMING = 'upcoming';
    case PAST = 'past';
    case NO_SHOW = 'noshow';

    public static function of(string $tab): self
    {
        return self::tryFrom($tab) ?? self::UPCOMING;
    }

    public function label(): string
    {
        return match ($this) {
            self::UPCOMING => Translation::translate('À venir'),
            self::PAST => Translation::translate('Passées'),
            self::NO_SHOW => Translation::translate('Absences'),
        };
    }

    public function statuses(): array
    {
        return match ($this) {
            self::UPCOMING => [BookingStatus::CONFIRMED],
            self::PAST => [
                BookingStatus::CONFIRMED,
                BookingStatus::HONORED,
                BookingStatus::NO_SHOW,
                BookingStatus::CANCELLED_BY_CUSTOMER,
                BookingStatus::CANCELLED_BY_ESTABLISHMENT,
            ],
            self::NO_SHOW => [BookingStatus::NO_SHOW],
        };
    }

    public function after(DateTimeImmutable $now): ?DateTimeImmutable
    {
        if ($this === self::UPCOMING) {
            return $now;
        }

        return null;
    }

    public function before(DateTimeImmutable $now): ?DateTimeImmutable
    {
        if ($this === self::PAST) {
            return $now;
        }

        return null;
    }
}