<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class PlanNotOfferedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cette formule n\'est pas proposée.');
    }
}