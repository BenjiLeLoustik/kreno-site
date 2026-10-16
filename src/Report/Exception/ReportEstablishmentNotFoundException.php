<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class ReportEstablishmentNotFoundException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Établissement introuvable.');
    }
}