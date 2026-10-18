<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class ScheduledVersionBlocksRestoreException extends BusinessRuleException
{
    public function __construct(string $code)
    {
        parent::__construct(
            Translation::translate('La {code} est planifiée. Annulez sa planification avant de restaurer une ancienne version.'),
            [
                'code' => $code
            ],
        );
    }
}