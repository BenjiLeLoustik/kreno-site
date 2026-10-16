<?php

namespace App\Support\Enum;

enum AppContactStatus: string
{
    case NEW = 'new';
    case ANSWERED = 'answered';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Nouvelle',
            self::ANSWERED => 'Répondue',
            self::CLOSED => 'Fermée',
        };
    }

    public function customerLabel(): string
    {
        return match ($this) {
            self::NEW => 'En attente de réponse',
            self::ANSWERED => 'Réponse reçue',
            self::CLOSED => 'Fermée',
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