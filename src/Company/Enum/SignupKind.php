<?php

namespace App\Company\Enum;

use App\Shared\Helper\Translation;

enum SignupKind: string
{
    case SOLO = 'solo';
    case TEAM = 'team';

    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $kind) {
            $choices[] = [
                'value' => $kind->value,
                'title' => $kind->title(),
                'text' => $kind->text()
            ];
        }

        return $choices;
    }

    public function title(): string
    {
        return match ($this) {
            self::SOLO => Translation::translate('Je travaille seul·e'),
            self::TEAM => Translation::translate('J’ai une équipe'),
        };
    }

    public function text(): string
    {
        return match ($this) {
            self::SOLO => Translation::translate('Indépendant, auto-entrepreneur'),
            self::TEAM => Translation::translate('Salon, cabinet, atelier avec employés'),
        };
    }
}