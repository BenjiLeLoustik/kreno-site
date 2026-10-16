<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class OfferRequiresSubscriptionException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cette offre concerne les abonnements.');
    }
}