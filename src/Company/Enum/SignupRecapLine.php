<?php

namespace App\Company\Enum;

use App\Shared\Helper\Translation;

enum SignupRecapLine
{
    case ACCOUNT;
    case ESTABLISHMENT;
    case SIRET;
    case SERVICES;
    case PAGE;
    case PLAN;

    public function key(): string
    {
        return match ($this) {
            self::ACCOUNT => ProSignupStep::ACCOUNT->value,
            self::ESTABLISHMENT, self::SIRET => ProSignupStep::ESTABLISHMENT->value,
            self::SERVICES => ProSignupStep::SERVICES->value,
            self::PAGE => ProSignupStep::PAGE->value,
            self::PLAN => ProSignupStep::PLAN->value,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ACCOUNT => Translation::translate('Compte'),
            self::ESTABLISHMENT => Translation::translate('Établissement'),
            self::SIRET => Translation::translate('SIRET'),
            self::SERVICES => Translation::translate('Prestations'),
            self::PAGE => Translation::translate('Page publique'),
            self::PLAN => Translation::translate('Formule'),
        };
    }

    public function row(?string $value): array
    {
        return [
            'key' => $this->key(),
            'label' => $this->label(),
            'value' => $value
        ];
    }
}