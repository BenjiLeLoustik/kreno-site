<?php

namespace App\Company\Enum;

enum CompanyFlash
{
    case UPDATED;
    case ESTABLISHMENT_CREATED;
    case ESTABLISHMENT_SUBMITTED;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::UPDATED => 'Informations de l\'entreprise enregistrées.',
            self::ESTABLISHMENT_CREATED => '%s est créé en brouillon. Complétez-le puis envoyez-le en vérification.',
            self::ESTABLISHMENT_SUBMITTED => '%s est envoyé à l\'équipe Kréno pour vérification.',
        };
    }
}