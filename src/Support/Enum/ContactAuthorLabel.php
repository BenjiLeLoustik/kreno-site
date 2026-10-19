<?php

namespace App\Support\Enum;

use App\Shared\Helper\Translation;

enum ContactAuthorLabel
{
    case TEAM_MEMBER;
    case TEAM;
    case CUSTOMER;
    case YOU;

    public function text(string ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::TEAM_MEMBER => Translation::translate('%s · équipe'),
            self::TEAM => Translation::translate('Équipe Kréno'),
            self::CUSTOMER => Translation::translate('Client'),
            self::YOU => Translation::translate('Vous'),
        };
    }
}