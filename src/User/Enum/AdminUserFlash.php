<?php

namespace App\User\Enum;

enum AdminUserFlash
{
    case SUSPENDED;
    case LIFTED;
    case BANNED;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::SUSPENDED => 'Compte suspendu %d jours. L\'utilisateur a été prévenu par e-mail.',
            self::LIFTED => 'Compte réactivé.',
            self::BANNED => 'Compte banni.',
        };
    }
}