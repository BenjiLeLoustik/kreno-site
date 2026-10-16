<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class SingleOwnerException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Il ne peut y avoir qu\'un seul propriétaire.');
    }
}