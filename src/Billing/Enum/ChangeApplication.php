<?php

namespace App\Billing\Enum;

use App\Shared\Helper\Translation;

enum ChangeApplication: string
{
    case IMMEDIATE = 'immediate';
    case RENEWAL = 'renewal';

    public function label(): string
    {
        return match ($this) {
            self::IMMEDIATE => Translation::translate('Immédiatement, avec prorata'),
            self::RENEWAL => Translation::translate('À la prochaine échéance'),
        };
    }

    public function logLabel(): string
    {
        return match ($this) {
            self::IMMEDIATE => 'immédiatement',
            self::RENEWAL => 'à la prochaine échéance',
        };
    }
}