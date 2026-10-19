<?php

namespace App\Support\Enum;

use App\Shared\Helper\Translation;

enum AppContactStatus: string
{
    case NEW = 'new';
    case ANSWERED = 'answered';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::NEW => Translation::translate('Nouvelle'),
            self::ANSWERED => Translation::translate('Répondue'),
            self::CLOSED => Translation::translate('Fermée'),
        };
    }

    public function customerLabel(): string
    {
        return match ($this) {
            self::NEW => Translation::translate('En attente de réponse'),
            self::ANSWERED => Translation::translate('Réponse reçue'),
            self::CLOSED => Translation::translate('Fermée'),
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::NEW => 'danger',
            self::ANSWERED => 'success',
            self::CLOSED => 'info',
        };
    }
}