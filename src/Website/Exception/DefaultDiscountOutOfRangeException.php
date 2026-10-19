<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class DefaultDiscountOutOfRangeException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('La remise par défaut doit être comprise entre la minimale et la maximale.')
        );
    }
}