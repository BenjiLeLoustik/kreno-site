<?php

namespace App\Company\Enum;

use App\Shared\Helper\Translation;

enum ModerationFlash
{
    case APPROVED;
    case REJECTED;
    case DOCUMENT_REQUESTED;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::APPROVED => Translation::translate('%s est validé et en ligne. Le pro a été prévenu par e-mail.'),
            self::REJECTED => Translation::translate('%s est refusé. Le pro a été prévenu par e-mail.'),
            self::DOCUMENT_REQUESTED => Translation::translate('Justificatif demandé à %s par e-mail.'),
        };
    }
}