<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class NoCurrentSubscriptionException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Aucun abonnement en cours.');
    }
}