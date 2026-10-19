<?php

declare(strict_types=1);

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

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
            self::DRAFT => Translation::translate('Brouillon'),
            self::PENDING_REVIEW => Translation::translate('En vérification'),
            self::ONLINE => Translation::translate('En ligne'),
            self::PAUSED => Translation::translate('En pause'),
            self::REJECTED => Translation::translate('Refusé'),
        };
    }
}