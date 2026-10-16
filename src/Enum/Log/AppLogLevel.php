<?php

namespace App\Enum\Log;

enum AppLogLevel: string
{
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::WARNING => 'Avertissement',
            self::ERROR => 'Erreur',
            self::CRITICAL => 'Critique',
        };
    }
}