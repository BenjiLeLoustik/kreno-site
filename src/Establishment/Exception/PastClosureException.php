<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class PastClosureException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cette fermeture est déjà passée.');
    }
}