<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class NoPlanChosenException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Aucune formule choisie.');
    }
}