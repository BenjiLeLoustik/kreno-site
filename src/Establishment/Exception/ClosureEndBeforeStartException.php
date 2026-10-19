<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class ClosureEndBeforeStartException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('La date de fin doit suivre la date de début.')
        );
    }
}