<?php

namespace App\Company\Enum;

use App\Shared\Helper\Translation;

enum ContextFlash
{
    case SWITCHED;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::SWITCHED => Translation::translate('Vous gérez maintenant %s.'),
        };
    }
}