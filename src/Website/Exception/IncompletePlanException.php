<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class IncompletePlanException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Chaque formule active doit avoir un code et un nom.')
        );
    }
}