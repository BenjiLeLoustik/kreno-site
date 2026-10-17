<?php

namespace App\Review\Enum;

enum ReviewStatus: string
{
    case PUBLISHED = 'published';
    case REPORTED = 'reported';
    case HIDDEN = 'hidden';

    public static function visible(): array
    {
        return [self::PUBLISHED, self::REPORTED];
    }

    public static function visibleValues(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::visible());
    }

    public function isVisible(): bool
    {
        return $this !== self::HIDDEN;
    }

    public function tone(): string
    {
        return match ($this) {
            self::PUBLISHED => 'success',
            self::REPORTED => 'warning',
            self::HIDDEN => 'danger',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::PUBLISHED => 'Publié',
            self::REPORTED => 'Signalé',
            self::HIDDEN => 'Masqué',
        };
    }

    public function proLabel(): string
    {
        return match ($this) {
            self::PUBLISHED => 'En ligne',
            self::REPORTED => 'En cours d\'examen',
            self::HIDDEN => 'Masqué par l\'équipe',
        };
    }
}