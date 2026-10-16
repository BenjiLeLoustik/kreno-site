<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class AlertTimeRangeException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('L\'heure de fin doit être après l\'heure de début.');
    }
}