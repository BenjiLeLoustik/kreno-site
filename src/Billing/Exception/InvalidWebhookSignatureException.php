<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class InvalidWebhookSignatureException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Signature de notification invalide.');
    }
}