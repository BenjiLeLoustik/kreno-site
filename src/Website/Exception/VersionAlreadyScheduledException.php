<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class VersionAlreadyScheduledException extends BusinessRuleException
{
    public function __construct(string $code)
    {
        parent::__construct(
            Translation::translate('La {code} est déjà planifiée. Annulez sa planification avant d\'en planifier une autre.'),
            [
                'code' => $code
            ],
        );
    }
}