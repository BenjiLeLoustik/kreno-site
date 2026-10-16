<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class GuestEmailExistsException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Un compte existe déjà avec cette adresse e-mail : connecte-toi pour réserver.');
    }
}