<?php

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

enum EstablishmentFlash
{
    case UPDATED;
    case HOURS_SAVED;
    case CLOSURE_ADDED;
    case CLOSURE_DELETED;

    public function text(): string
    {
        return match ($this) {
            self::UPDATED => Translation::translate('Informations enregistrées.'),
            self::HOURS_SAVED => Translation::translate('Horaires enregistrés.'),
            self::CLOSURE_ADDED => Translation::translate('Fermeture exceptionnelle ajoutée.'),
            self::CLOSURE_DELETED => Translation::translate('Fermeture supprimée.'),
        };
    }
}