<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class MemberWithoutUserException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Aucun utilisateur.');
    }
}