<?php

namespace App\Website\Enum;

use App\Shared\Helper\Translation;

enum RuleError
{
    case MIN_ABOVE_MAX;
    case DEFAULT_OUT_OF_RANGE;
    case AUTO_REMOVAL_TOO_LATE;

    public function message(): string
    {
        return match ($this) {
            self::MIN_ABOVE_MAX => Translation::translate('la remise minimale doit être inférieure à la maximale'),
            self::DEFAULT_OUT_OF_RANGE => Translation::translate('la remise par défaut doit être comprise entre la minimale et la maximale'),
            self::AUTO_REMOVAL_TOO_LATE => Translation::translate('le retrait automatique doit arriver avant la fin de l\'annulation gratuite'),
        };
    }
}