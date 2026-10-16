<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class ReportBookingNotFoundException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Réservation introuvable.');
    }
}