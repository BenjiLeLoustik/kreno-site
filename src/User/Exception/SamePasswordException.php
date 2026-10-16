<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class SamePasswordException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Le nouveau mot de passe doit être différent de l\'actuel.');
    }
}