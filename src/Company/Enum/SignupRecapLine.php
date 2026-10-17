<?php

namespace App\Company\Enum;

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
            self::ACCOUNT => 'Compte',
            self::ESTABLISHMENT => 'Établissement',
            self::SIRET => 'SIRET',
            self::SERVICES => 'Prestations',
            self::PAGE => 'Page publique',
            self::PLAN => 'Formule',
        };
    }

    public function row(?string $value): array
    {
        return ['key' => $this->key(), 'label' => $this->label(), 'value' => $value];
    }
}