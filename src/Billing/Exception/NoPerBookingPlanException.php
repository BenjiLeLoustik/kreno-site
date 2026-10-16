<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class NoPerBookingPlanException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Aucune formule « à la réservation » n\'est proposée.');
    }
}