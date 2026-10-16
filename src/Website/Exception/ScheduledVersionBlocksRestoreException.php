<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class ScheduledVersionBlocksRestoreException extends BusinessRuleException
{
    public function __construct(string $code)
    {
        parent::__construct(
            'La {code} est planifiée. Annulez sa planification avant de restaurer une ancienne version.',
            [
                'code' => $code
            ],
        );
    }
}