<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class BookingRequiredForReasonException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce motif concerne une réservation : choisis-la.');
    }
}