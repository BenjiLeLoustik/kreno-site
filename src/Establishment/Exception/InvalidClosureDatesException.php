<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class InvalidClosureDatesException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Les dates de fermeture sont invalides.')
        );
    }
}