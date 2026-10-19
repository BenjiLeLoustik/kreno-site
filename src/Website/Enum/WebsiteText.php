<?php

namespace App\Website\Enum;

use App\Shared\Helper\Translation;

enum WebsiteText
{
    case RULES_SUMMARY;

    public function text(string|int ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::RULES_SUMMARY => Translation::translate('Annulation gratuite jusqu\'à %s avant le rendez-vous. Après %d absences non prévenues en %d jours, ton compte est suspendu %d jours.'),
        };
    }
}