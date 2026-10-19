<?php

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

enum SlotKind: string
{
    case ONLINE = 'online';
    case BOOKED = 'booked';
    case EXPIRED = 'expired';
    case REMOVED = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::ONLINE => Translation::translate('En ligne'),
            self::BOOKED => Translation::translate('Réservé'),
            self::EXPIRED => Translation::translate('Expiré'),
            self::REMOVED => Translation::translate('Retiré'),
        };
    }

    public function agendaText(string $client): string
    {
        return match ($this) {
            self::ONLINE => Translation::translate('en ligne, pas encore réservé'),
            self::BOOKED => sprintf(Translation::translate('réservé par %s'), $client),
            self::EXPIRED => Translation::translate('expiré sans réservation'),
            self::REMOVED => Translation::translate('retiré'),
        };
    }

    public function cta(): string
    {
        return match ($this) {
            self::ONLINE => Translation::translate('Modifier dans Mes créneaux'),
            self::BOOKED => Translation::translate('Voir la réservation'),
            self::EXPIRED, self::REMOVED => Translation::translate('Republier'),
        };
    }

    public function action(): string
    {
        return match ($this) {
            self::ONLINE => 'slots',
            self::BOOKED => 'booking',
            self::EXPIRED, self::REMOVED => 'republish',
        };
    }

    public function isRepublishable(): bool
    {
        return $this === self::EXPIRED || $this === self::REMOVED;
    }
}