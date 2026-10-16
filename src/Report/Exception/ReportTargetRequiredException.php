<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class ReportTargetRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Choisis la réservation ou l\'établissement concerné.');
    }
}