<?php

namespace App\Report\Enum;

enum AdminReportFlash
{
    case REVIEWED;
    case NOTE_ADDED;
    case DECIDED;

    public function text(): string
    {
        return match ($this) {
            self::REVIEWED => 'Signalement pris en charge.',
            self::NOTE_ADDED => 'Note interne ajoutée.',
            self::DECIDED => 'Décision enregistrée. Les deux parties sont prévenues par e-mail.',
        };
    }
}