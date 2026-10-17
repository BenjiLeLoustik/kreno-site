<?php

namespace App\Website\Enum;

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
            self::PLAN_CREATED => 'Formule « %s » ajoutée.',
            self::PLAN_UPDATED => 'Formule « %s » modifiée.',
            self::PROMO_CREATED => 'Code %s créé.',
            self::PROMO_UPDATED => 'Code %s modifié.',
            self::POLICY_SAVED => 'Politique de changement de prix enregistrée.',
        };
    }
}