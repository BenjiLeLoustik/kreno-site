<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class VersionNotScheduledException extends BusinessRuleException
{
    public function __construct(string $code)
    {
        parent::__construct(
            Translation::translate('La {code} n\'est pas planifiée.'),
            [
                'code' => $code
            ]
        );
    }
}