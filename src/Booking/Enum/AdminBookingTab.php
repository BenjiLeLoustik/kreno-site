<?php

namespace App\Booking\Enum;

enum AdminBookingTab: string
{
    case ALL = 'all';
    case COMING = 'coming';
    case DONE = 'done';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'noshow';

    public static function of(string $tab): self
    {
        return self::tryFrom($tab) ?? self::ALL;
    }

    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $tab) {
            $choices[] = ['key' => $tab->value, 'label' => $tab->label()];
        }

        return $choices;
    }

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'Toutes',
            self::COMING => 'À venir',
            self::DONE => 'Honorées',
            self::CANCELLED => 'Annulées',
            self::NO_SHOW => 'Absences',
        };
    }

    public function statuses(): array
    {
        return match ($this) {
            self::ALL => [
                BookingStatus::CONFIRMED,
                BookingStatus::HONORED,
                BookingStatus::NO_SHOW,
                BookingStatus::CANCELLED_BY_CUSTOMER,
                BookingStatus::CANCELLED_BY_ESTABLISHMENT,
            ],
            self::COMING => [BookingStatus::CONFIRMED],
            self::DONE => [BookingStatus::HONORED],
            self::CANCELLED => [BookingStatus::CANCELLED_BY_CUSTOMER, BookingStatus::CANCELLED_BY_ESTABLISHMENT],
            self::NO_SHOW => [BookingStatus::NO_SHOW],
        };
    }
}