<?php

declare(strict_types=1);

namespace App\Establishment\Enum;

enum EstablishmentStatus: string
{
    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case ONLINE = 'online';
    case PAUSED = 'paused';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::PENDING_REVIEW => 'En vérification',
            self::ONLINE => 'En ligne',
            self::PAUSED => 'En pause',
            self::REJECTED => 'Refusé',
        };
    }
}