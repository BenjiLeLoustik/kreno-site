<?php

namespace App\Review\Enum;

use App\Shared\Helper\Translation;

enum ReviewText
{
    case ANONYMOUS;
    case COUNT;
    case RECEIVED_TITLE;
    case REPLIED_TITLE;
    case HIDDEN_TITLE;
    case REPORT_ACCEPTED_TITLE;
    case REPORT_REJECTED_TITLE;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::ANONYMOUS => Translation::translate('Ancien client'),
            self::COUNT => Translation::translate('%d avis'),
            self::RECEIVED_TITLE => Translation::translate('Nouvel avis %d/5 sur %s'),
            self::REPLIED_TITLE => Translation::translate('%s a répondu à ton avis'),
            self::HIDDEN_TITLE => Translation::translate('Ton avis sur %s a été masqué'),
            self::REPORT_ACCEPTED_TITLE => Translation::translate('Avis masqué sur %s après examen'),
            self::REPORT_REJECTED_TITLE => Translation::translate('Avis maintenu sur %s après examen'),
        };
    }
}