<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class ClosureEndBeforeStartException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('La date de fin doit suivre la date de début.');
    }
}