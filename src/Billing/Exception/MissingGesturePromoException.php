<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class MissingGesturePromoException extends BusinessRuleException
{
    public function __construct(string $code)
    {
        parent::__construct('Créez d\'abord le code {code} dans « Tarifs et abonnements ».', ['code' => $code]);
    }
}