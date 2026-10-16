<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class ServiceNotOfferedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cette prestation n\'existe pas ou n\'est plus proposée.');
    }
}