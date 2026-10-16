<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class PauseNotAllowedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Seul un abonnement actif peut être mis en pause.');
    }
}