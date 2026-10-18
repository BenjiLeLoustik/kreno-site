<?php

namespace App\Catalog\Enum;

use App\Shared\Helper\Translation;

enum CatalogFlash
{
    case CATEGORY_CREATED;
    case CATEGORIES_REORDERED;
    case CATEGORY_UPDATED;
    case CATEGORY_SHOWN;
    case CATEGORY_HIDDEN;
    case CATEGORY_DELETED;
    case SUGGESTION_CREATED;
    case SUGGESTION_UPDATED;
    case SUGGESTION_DELETED;
    case CITY_CREATED;
    case CITIES_REORDERED;
    case CITY_UPDATED;
    case CITY_STATUS;
    case CITY_DELETED;

    public static function categoryVisibility(bool $visible): self
    {
        if ($visible) {
            return self::CATEGORY_SHOWN;
        }

        return self::CATEGORY_HIDDEN;
    }

    public static function cityStatus(?string $city, CityStatus $status): string
    {
        return self::CITY_STATUS->text($city, mb_strtolower($status->label()));
    }

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::CATEGORY_CREATED => Translation::translate('Catégorie « %s » ajoutée.'),
            self::CATEGORIES_REORDERED => Translation::translate('Ordre des catégories enregistré.'),
            self::CATEGORY_UPDATED => Translation::translate('Catégorie « %s » modifiée.'),
            self::CATEGORY_SHOWN => Translation::translate('Catégorie « %s » visible.'),
            self::CATEGORY_HIDDEN => Translation::translate('Catégorie « %s » masquée.'),
            self::CATEGORY_DELETED => Translation::translate('Catégorie « %s » supprimée.'),
            self::SUGGESTION_CREATED => Translation::translate('Suggestion « %s » ajoutée.'),
            self::SUGGESTION_UPDATED => Translation::translate('Suggestion « %s » modifiée.'),
            self::SUGGESTION_DELETED => Translation::translate('Suggestion « %s » supprimée.'),
            self::CITY_CREATED => Translation::translate('Ville « %s » ajoutée.'),
            self::CITIES_REORDERED => Translation::translate('Ordre des villes enregistré.'),
            self::CITY_UPDATED => Translation::translate('Ville « %s » modifiée.'),
            self::CITY_STATUS => Translation::translate('%s : %s.'),
            self::CITY_DELETED => Translation::translate('Ville « %s » supprimée.'),
        };
    }
}