<?php

namespace App\Billing\Enum;

enum ChangeApplication: string
{
    case IMMEDIATE = 'immediate';
    case RENEWAL = 'renewal';

    public function label(): string
    {
        return match ($this) {
            self::IMMEDIATE => 'Immédiatement, avec prorata',
            self::RENEWAL => 'À la prochaine échéance',
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