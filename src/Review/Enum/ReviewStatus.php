<?php

namespace App\Review\Enum;

use App\Shared\Helper\Translation;

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
            self::PUBLISHED => Translation::translate('Publié'),
            self::REPORTED => Translation::translate('Signalé'),
            self::HIDDEN => Translation::translate('Masqué'),
        };
    }

    public function proLabel(): string
    {
        return match ($this) {
            self::PUBLISHED => Translation::translate('En ligne'),
            self::REPORTED => Translation::translate('En cours d\'examen'),
            self::HIDDEN => Translation::translate('Masqué par l\'équipe'),
        };
    }
}