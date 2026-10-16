<?php

namespace App\Support\Enum;

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
            $choices[] = ['value' => $topic->value, 'label' => $topic->label()];
        }

        return $choices;
    }

    public function label(): string
    {
        return match ($this) {
            self::BOOKING => 'Une réservation',
            self::ACCOUNT => 'Mon compte',
            self::REPORT => 'Signaler un établissement',
            self::OTHER => 'Autre',
        };
    }
}