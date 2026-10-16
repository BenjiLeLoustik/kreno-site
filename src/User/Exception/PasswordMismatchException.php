<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class PasswordMismatchException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Les deux mots de passe ne sont pas identiques.');
    }
}