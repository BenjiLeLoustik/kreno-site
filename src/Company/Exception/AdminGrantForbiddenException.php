<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class AdminGrantForbiddenException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Seuls le propriétaire et les administrateurs peuvent nommer un administrateur.');
    }
}