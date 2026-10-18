<?php

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

enum ImageFlash
{
    case ADDED;
    case ALT_SAVED;
    case DELETED;
    case REORDERED;

    public function text(): string
    {
        return match ($this) {
            self::ADDED => Translation::translate('Photo ajoutée.'),
            self::ALT_SAVED => Translation::translate('Description de la photo enregistrée.'),
            self::DELETED => Translation::translate('Photo supprimée.'),
            self::REORDERED => Translation::translate('Ordre des photos enregistré.'),
        };
    }
}