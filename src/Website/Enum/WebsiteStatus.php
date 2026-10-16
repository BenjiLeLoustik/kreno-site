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
}