<?php

namespace App\Catalog\Enum;

enum CatalogText
{
    case HOME;

    public function text(): string
    {
        return match ($this) {
            self::HOME => 'Accueil',
        };
    }
}