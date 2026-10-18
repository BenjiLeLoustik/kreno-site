<?php

namespace App\Support\Enum;

use App\Shared\Helper\Translation;

enum ContactTopic: string
{
    case BOOKING = 'booking';
    case ACCOUNT = 'account';
    case REPORT = 'report';
    case OTHER = 'other';

    public static function of(?string $topic): self
    {
        return self::tryFrom((string) $topic) ?? self::OTHER;
    }

    public static function values(): array
    {
        $values = [];

        foreach (self::cases() as $topic) {
            $values[] = $topic->value;
        }

        return $values;
    }

    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $topic) {
            $choices[] = [
                'value' => $topic->value,
                'label' => $topic->label()
            ];
        }

        return $choices;
    }

    public function label(): string
    {
        return match ($this) {
            self::BOOKING => Translation::translate('Une réservation'),
            self::ACCOUNT => Translation::translate('Mon compte'),
            self::REPORT => Translation::translate('Signaler un établissement'),
            self::OTHER => Translation::translate('Autre'),
        };
    }
}