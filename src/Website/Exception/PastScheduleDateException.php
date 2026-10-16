<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class PastScheduleDateException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('La date de mise en ligne doit être dans le futur.');
    }
}