<?php

namespace App\Company\Enum;

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
            self::APPROVED => '%s est validé et en ligne. Le pro a été prévenu par e-mail.',
            self::REJECTED => '%s est refusé. Le pro a été prévenu par e-mail.',
            self::DOCUMENT_REQUESTED => 'Justificatif demandé à %s par e-mail.',
        };
    }
}