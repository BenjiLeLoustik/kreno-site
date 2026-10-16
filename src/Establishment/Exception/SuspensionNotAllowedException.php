<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class SuspensionNotAllowedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Seul un établissement en ligne peut être suspendu.');
    }
}