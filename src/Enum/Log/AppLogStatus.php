<?php

namespace App\Enum\Log;

enum AppLogStatus: string
{
    case NEW = 'new';
    case ACKNOWLEDGED = 'acknowledged';
    case RESOLVED = 'resolved';
    case IGNORED = 'ignored';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Nouvelle',
            self::ACKNOWLEDGED => 'Vue',
            self::RESOLVED => 'Corrigée',
            self::IGNORED => 'Ignorée',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::NEW => 'danger',
            self::ACKNOWLEDGED => 'warning',
            self::RESOLVED => 'success',
            self::IGNORED => 'info',
        };
    }
}