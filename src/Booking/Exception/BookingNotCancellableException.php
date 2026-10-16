<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;

class BookingNotCancellableException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cette réservation ne peut plus être annulée.');
    }
}