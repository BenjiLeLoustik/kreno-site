<?php

namespace App\Company\Enum;

use App\Entity\Company\CompanyEstablishment;
use App\Shared\Helper\Translation;

enum EstablishmentRequirement: string
{
    case SIRET = 'siret';
    case CITY = 'city';
    case CATEGORY = 'category';
    case ADDRESS = 'address';

    public function label(): string
    {
        return match ($this) {
            self::SIRET => Translation::translate('le SIRET'),
            self::CITY => Translation::translate('la ville'),
            self::CATEGORY => Translation::translate('la catégorie'),
            self::ADDRESS => Translation::translate('l\'adresse'),
        };
    }

    public function isMissing(CompanyEstablishment $establishment): bool
    {
        return match ($this) {
            self::SIRET => $establishment->getSiret() === null,
            self::CITY => $establishment->getCity() === null,
            self::CATEGORY => $establishment->getCategory() === null,
            self::ADDRESS => $establishment->getAddressLine1() === null,
        };
    }
}