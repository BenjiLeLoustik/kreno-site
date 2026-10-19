<?php

namespace App\Review\Enum;

use App\Shared\Helper\Translation;

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
            self::CREATED => Translation::translate('Merci, ton avis est publié.'),
            self::REPLIED => Translation::translate('Votre réponse est publiée.'),
            self::REPORTED => Translation::translate('Signalement envoyé : notre équipe va examiner cet avis.'),
            self::HIDDEN => Translation::translate('Avis masqué.'),
            self::PUBLISHED => Translation::translate('Avis remis en ligne.'),
        };
    }
}