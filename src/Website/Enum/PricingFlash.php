<?php

namespace App\Website\Enum;

use App\Shared\Helper\Translation;

enum PricingFlash
{
    case PLAN_CREATED;
    case PLAN_UPDATED;
    case PROMO_CREATED;
    case PROMO_UPDATED;
    case POLICY_SAVED;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::PLAN_CREATED => Translation::translate('Formule « %s » ajoutée.'),
            self::PLAN_UPDATED => Translation::translate('Formule « %s » modifiée.'),
            self::PROMO_CREATED => Translation::translate('Code %s créé.'),
            self::PROMO_UPDATED => Translation::translate('Code %s modifié.'),
            self::POLICY_SAVED => Translation::translate('Politique de changement de prix enregistrée.'),
        };
    }
}