<?php

namespace App\Company\Enum;

enum SignupKind: string
{
    case SOLO = 'solo';
    case TEAM = 'team';

    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $kind) {
            $choices[] = ['value' => $kind->value, 'title' => $kind->title(), 'text' => $kind->text()];
        }

        return $choices;
    }

    public function title(): string
    {
        return match ($this) {
            self::SOLO => 'Je travaille seul·e',
            self::TEAM => 'J’ai une équipe',
        };
    }

    public function text(): string
    {
        return match ($this) {
            self::SOLO => 'Indépendant, auto-entrepreneur',
            self::TEAM => 'Salon, cabinet, atelier avec employés',
        };
    }
}