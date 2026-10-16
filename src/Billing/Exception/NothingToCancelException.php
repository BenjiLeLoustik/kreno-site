<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class NothingToCancelException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Votre formule est sans abonnement : il n\'y a rien à résilier.');
    }
}