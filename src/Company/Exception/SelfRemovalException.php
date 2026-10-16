<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class SelfRemovalException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Vous ne pouvez pas vous retirer vous-même.');
    }
}