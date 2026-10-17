<?php

namespace App\Review\Enum;

enum ReviewFlash
{
    case CREATED;
    case REPLIED;
    case REPORTED;
    case HIDDEN;
    case PUBLISHED;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::CREATED => 'Merci, ton avis est publié.',
            self::REPLIED => 'Votre réponse est publiée.',
            self::REPORTED => 'Signalement envoyé : notre équipe va examiner cet avis.',
            self::HIDDEN => 'Avis masqué.',
            self::PUBLISHED => 'Avis remis en ligne.',
        };
    }
}