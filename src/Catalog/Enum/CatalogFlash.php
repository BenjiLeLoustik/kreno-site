<?php

namespace App\Catalog\Enum;

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
            self::CATEGORY_CREATED => 'Catégorie « %s » ajoutée.',
            self::CATEGORIES_REORDERED => 'Ordre des catégories enregistré.',
            self::CATEGORY_UPDATED => 'Catégorie « %s » modifiée.',
            self::CATEGORY_SHOWN => 'Catégorie « %s » visible.',
            self::CATEGORY_HIDDEN => 'Catégorie « %s » masquée.',
            self::CATEGORY_DELETED => 'Catégorie « %s » supprimée.',
            self::SUGGESTION_CREATED => 'Suggestion « %s » ajoutée.',
            self::SUGGESTION_UPDATED => 'Suggestion « %s » modifiée.',
            self::SUGGESTION_DELETED => 'Suggestion « %s » supprimée.',
            self::CITY_CREATED => 'Ville « %s » ajoutée.',
            self::CITIES_REORDERED => 'Ordre des villes enregistré.',
            self::CITY_UPDATED => 'Ville « %s » modifiée.',
            self::CITY_STATUS => '%s : %s.',
            self::CITY_DELETED => 'Ville « %s » supprimée.',
        };
    }
}