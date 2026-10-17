<?php

namespace App\Establishment\Enum;

enum EstablishmentFlash
{
    case UPDATED;
    case HOURS_SAVED;
    case CLOSURE_ADDED;
    case CLOSURE_DELETED;

    public function text(): string
    {
        return match ($this) {
            self::UPDATED => 'Informations enregistrées.',
            self::HOURS_SAVED => 'Horaires enregistrés.',
            self::CLOSURE_ADDED => 'Fermeture exceptionnelle ajoutée.',
            self::CLOSURE_DELETED => 'Fermeture supprimée.',
        };
    }
}