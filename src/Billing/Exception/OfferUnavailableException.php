<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class OfferUnavailableException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cette offre n\'est plus disponible.');
    }
}