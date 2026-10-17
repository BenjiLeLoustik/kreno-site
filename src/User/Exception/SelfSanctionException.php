<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class SelfSanctionException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Vous ne pouvez pas vous sanctionner vous-même.');
    }
}