<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class EmailAlreadyUsedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cette adresse e-mail est déjà utilisée par un autre compte.');
    }
}