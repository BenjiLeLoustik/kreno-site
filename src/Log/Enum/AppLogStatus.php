<?php

namespace App\Log\Enum;

use App\Shared\Helper\Translation;

enum AppLogStatus: string
{
    case NEW = 'new';
    case ACKNOWLEDGED = 'acknowledged';
    case RESOLVED = 'resolved';
    case IGNORED = 'ignored';

    public function label(): string
    {
        return match ($this) {
            self::NEW => Translation::translate('Nouvelle'),
            self::ACKNOWLEDGED => Translation::translate('Vue'),
            self::RESOLVED => Translation::translate('Corrigée'),
            self::IGNORED => Translation::translate('Ignorée'),
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