<?php

namespace App\Catalog\Enum;

use App\Shared\Helper\Translation;

enum CatalogText
{
    case HOME;

    public function text(): string
    {
        return match ($this) {
            self::HOME => Translation::translate('Accueil'),
        };
    }
}