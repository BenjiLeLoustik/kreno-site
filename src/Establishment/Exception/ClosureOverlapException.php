<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class ClosureOverlapException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Une fermeture existe déjà sur cette période.');
    }
}