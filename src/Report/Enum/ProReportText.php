<?php

namespace App\Report\Enum;

use App\Shared\Helper\Translation;

enum ProReportText
{
    case NO_SHOW_NOTICE;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::NO_SHOW_NOTICE => Translation::translate('Une absence confirmée compte pour la suspension automatique du client (%d absences en %d jours).'),
        };
    }
}