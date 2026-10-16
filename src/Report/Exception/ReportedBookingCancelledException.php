<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class ReportedBookingCancelledException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cette réservation a été annulée.');
    }
}