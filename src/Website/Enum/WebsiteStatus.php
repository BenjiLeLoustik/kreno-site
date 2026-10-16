<?php

declare(strict_types=1);

namespace App\Website\Enum;

enum WebsiteStatus: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case LIVE = 'live';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::SCHEDULED => 'Planifiée',
            self::LIVE => 'En ligne',
            self::ARCHIVED => 'Archivée',
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