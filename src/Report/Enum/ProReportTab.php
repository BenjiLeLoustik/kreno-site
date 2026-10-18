<?php

namespace App\Report\Enum;

use App\Shared\Helper\Translation;

enum ProReportTab: string
{
    case MINE = 'mine';
    case RECEIVED = 'received';

    public static function resolve(string $tab, bool $canReport, bool $canSeeReceived): self
    {
        if (!$canReport) {
            return self::RECEIVED;
        }

        if ($tab === self::RECEIVED->value && $canSeeReceived) {
            return self::RECEIVED;
        }

        return self::MINE;
    }

    public function label(): string
    {
        return match ($this) {
            self::MINE => Translation::translate('Mes signalements'),
            self::RECEIVED => Translation::translate('Concernant l’établissement'),
        };
    }
}