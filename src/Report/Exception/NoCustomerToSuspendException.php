<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class NoCustomerToSuspendException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Aucun client à suspendre.');
    }
}