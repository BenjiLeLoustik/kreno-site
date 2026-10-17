<?php

declare(strict_types=1);

namespace App\User\Enum;

enum NotificationType: string
{
    case SLOT_ALERT = 'slot_alert';
    case BOOKING = 'booking';
    case REMINDER = 'reminder';
    case REPORT = 'report';
    case BILLING = 'billing';
    case INFO = 'info';
    case REVIEW = 'review';

    public function label(): string
    {
        return match ($this) {
            self::SLOT_ALERT => 'Alerte créneau',
            self::BOOKING => 'Réservation',
            self::REMINDER => 'Rappel',
            self::REPORT => 'Signalement',
            self::BILLING => 'Facturation',
            self::INFO => 'Information',
            self::REVIEW => 'Avis',
        };
    }
}