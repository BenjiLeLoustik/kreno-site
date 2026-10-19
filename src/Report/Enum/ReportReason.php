<?php

namespace App\Report\Enum;

use App\Shared\Helper\Translation;

enum ReportReason: string
{
    case PRICE_MISMATCH = 'price_mismatch';
    case CLOSED = 'closed';
    case SERVICE_MISMATCH = 'service_mismatch';
    case LAST_MINUTE_CANCELLATION = 'last_minute_cancellation';
    case BEHAVIOR = 'behavior';
    case NO_SHOW = 'no_show';
    case LATE = 'late';
    case OTHER = 'other';

    public static function forCustomer(): array
    {
        return [
            self::PRICE_MISMATCH,
            self::CLOSED,
            self::SERVICE_MISMATCH,
            self::LAST_MINUTE_CANCELLATION,
            self::BEHAVIOR,
            self::OTHER,
        ];
    }

    public static function forEstablishment(): array
    {
        return [
            self::NO_SHOW,
            self::LATE,
            self::BEHAVIOR,
            self::OTHER,
        ];
    }

    public static function forDirection(ReportDirection $direction): array
    {
        if ($direction === ReportDirection::FROM_CUSTOMER) {
            return self::forCustomer();
        }

        return self::forEstablishment();
    }

    public static function customerValues(): array
    {
        return self::valuesOf(self::forCustomer());
    }

    public static function establishmentValues(): array
    {
        return self::valuesOf(self::forEstablishment());
    }

    public function label(): string
    {
        return match ($this) {
            self::PRICE_MISMATCH => Translation::translate('Prix différent'),
            self::CLOSED => Translation::translate('Établissement fermé'),
            self::SERVICE_MISMATCH => Translation::translate('Prestation non conforme'),
            self::LAST_MINUTE_CANCELLATION => Translation::translate('Annulation de dernière minute'),
            self::BEHAVIOR => Translation::translate('Comportement inapproprié'),
            self::NO_SHOW => Translation::translate('Absence non prévenue'),
            self::LATE => Translation::translate('Retard important'),
            self::OTHER => Translation::translate('Autre'),
        };
    }

    public function hint(): ?string
    {
        return match ($this) {
            self::PRICE_MISMATCH => Translation::translate('Le prix demandé n’est pas celui affiché.'),
            self::CLOSED => Translation::translate('Personne à l’adresse au moment du rendez-vous.'),
            self::SERVICE_MISMATCH => Translation::translate('Ce n’est pas la prestation réservée.'),
            self::LAST_MINUTE_CANCELLATION => Translation::translate('L’établissement a annulé au dernier moment.'),
            self::BEHAVIOR => Translation::translate('Propos ou attitude déplacés.'),
            self::OTHER => Translation::translate('Un autre problème avec cette réservation.'),
            default => null,
        };
    }

    private static function valuesOf(array $reasons): array
    {
        $values = [];

        foreach ($reasons as $reason) {
            $values[] = $reason->value;
        }

        return $values;
    }
}