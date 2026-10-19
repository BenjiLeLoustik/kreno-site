<?php

namespace App\Log\Enum;

use App\Shared\Helper\Translation;

enum AppLogLevel: string
{
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::WARNING => Translation::translate('Avertissement'),
            self::ERROR => Translation::translate('Erreur'),
            self::CRITICAL => Translation::translate('Critique'),
        };
    }
}