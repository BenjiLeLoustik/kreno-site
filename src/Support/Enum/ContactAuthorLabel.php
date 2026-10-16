<?php

namespace App\Support\Enum;

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
            self::TEAM_MEMBER => '%s · équipe',
            self::TEAM => 'Équipe Kréno',
            self::CUSTOMER => 'Client',
            self::YOU => 'Vous',
        };
    }
}