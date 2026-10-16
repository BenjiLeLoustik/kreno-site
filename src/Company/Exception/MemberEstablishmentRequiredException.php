<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class MemberEstablishmentRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Choisissez l\'établissement de ce membre.');
    }
}