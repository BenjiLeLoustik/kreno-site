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
            self::ANONYMOUS => 'Ancien client',
            self::COUNT => '%d avis',
            self::RECEIVED_TITLE => 'Nouvel avis %d/5 sur %s',
            self::REPLIED_TITLE => '%s a répondu à ton avis',
            self::HIDDEN_TITLE => 'Ton avis sur %s a été masqué',
            self::REPORT_ACCEPTED_TITLE => 'Avis masqué sur %s après examen',
            self::REPORT_REJECTED_TITLE => 'Avis maintenu sur %s après examen',
        };
    }
}