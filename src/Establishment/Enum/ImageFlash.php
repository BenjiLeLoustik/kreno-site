<?php

namespace App\Establishment\Enum;

enum ImageFlash
{
    case ADDED;
    case ALT_SAVED;
    case DELETED;
    case REORDERED;

    public function text(): string
    {
        return match ($this) {
            self::ADDED => 'Photo ajoutée.',
            self::ALT_SAVED => 'Description de la photo enregistrée.',
            self::DELETED => 'Photo supprimée.',
            self::REORDERED => 'Ordre des photos enregistré.',
        };
    }
}