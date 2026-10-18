<?php

namespace App\Report\Enum;

use App\Shared\Helper\Translation;

enum AdminReportFlash
{
    case REVIEWED;
    case NOTE_ADDED;
    case DECIDED;

    public function text(): string
    {
        return match ($this) {
            self::REVIEWED => Translation::translate('Signalement pris en charge.'),
            self::NOTE_ADDED => Translation::translate('Note interne ajoutée.'),
            self::DECIDED => Translation::translate('Décision enregistrée. Les deux parties sont prévenues par e-mail.'),
        };
    }
}