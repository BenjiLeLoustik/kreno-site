<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class MinDiscountAboveMaxException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('La remise minimale doit être inférieure à la remise maximale.')
        );
    }
}