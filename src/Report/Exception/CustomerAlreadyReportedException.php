<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class CustomerAlreadyReportedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce client a déjà été signalé pour cette réservation.');
    }
}