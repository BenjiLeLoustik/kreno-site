<?php

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

enum SlotFilter: string
{
    case ALL = 'all';
    case ONLINE = 'online';
    case BOOKED = 'booked';
    case EXPIRED = 'expired';

    public static function of(string $filter): self
    {
        return self::tryFrom($filter) ?? self::ALL;
    }

    public function label(): string
    {
        return match ($this) {
            self::ALL => Translation::translate('Tous'),
            self::ONLINE => Translation::translate('En ligne'),
            self::BOOKED => Translation::translate('Réservés'),
            self::EXPIRED => Translation::translate('Expirés et retirés'),
        };
    }

    public function statuses(): array
    {
        return match ($this) {
            self::ALL => [SlotStatus::AVAILABLE, SlotStatus::BOOKED, SlotStatus::EXPIRED, SlotStatus::WITHDRAWN],
            self::ONLINE => [SlotStatus::AVAILABLE],
            self::BOOKED => [SlotStatus::BOOKED],
            self::EXPIRED => [SlotStatus::EXPIRED, SlotStatus::WITHDRAWN],
        };
    }
}