<?php

declare(strict_types=1);

namespace App\Website\Enum;

use App\Shared\Helper\Translation;

enum WebsiteStatus: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case LIVE = 'live';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => Translation::translate('Brouillon'),
            self::SCHEDULED => Translation::translate('Planifiée'),
            self::LIVE => Translation::translate('En ligne'),
            self::ARCHIVED => Translation::translate('Archivée'),
        };
    }

    public function isDeletable(): bool
    {
        return $this === self::DRAFT || $this === self::ARCHIVED;
    }

    public function isEditable(): bool
    {
        return $this === self::DRAFT || $this === self::SCHEDULED;
    }
}