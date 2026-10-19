<?php

declare(strict_types=1);

namespace App\User\Enum;

use App\Shared\Helper\Translation;

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
            self::SLOT_ALERT => Translation::translate('Alerte créneau'),
            self::BOOKING => Translation::translate('Réservation'),
            self::REMINDER => Translation::translate('Rappel'),
            self::REPORT => Translation::translate('Signalement'),
            self::BILLING => Translation::translate('Facturation'),
            self::INFO => Translation::translate('Information'),
            self::REVIEW => Translation::translate('Avis'),
        };
    }
}