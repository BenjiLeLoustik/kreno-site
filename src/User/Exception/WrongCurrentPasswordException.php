<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class WrongCurrentPasswordException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Le mot de passe actuel est incorrect.');
    }
}