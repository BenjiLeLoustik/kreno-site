<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;

class NoCancellationScheduledException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Aucune résiliation n\'est prévue.');
    }
}