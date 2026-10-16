<?php

namespace App\Establishment\Enum;

enum SlotKind: string
{
    case ONLINE = 'online';
    case BOOKED = 'booked';
    case EXPIRED = 'expired';
    case REMOVED = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::ONLINE => 'En ligne',
            self::BOOKED => 'Réservé',
            self::EXPIRED => 'Expiré',
            self::REMOVED => 'Retiré',
        };
    }

    public function agendaText(string $client): string
    {
        return match ($this) {
            self::ONLINE => 'en ligne, pas encore réservé',
            self::BOOKED => sprintf('réservé par %s', $client),
            self::EXPIRED => 'expiré sans réservation',
            self::REMOVED => 'retiré',
        };
    }

    public function cta(): string
    {
        return match ($this) {
            self::ONLINE => 'Modifier dans Mes créneaux',
            self::BOOKED => 'Voir la réservation',
            self::EXPIRED, self::REMOVED => 'Republier',
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