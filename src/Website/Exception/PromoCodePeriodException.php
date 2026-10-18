<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class PromoCodePeriodException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('La fin de validité du code doit être après son début.')
        );
    }
}