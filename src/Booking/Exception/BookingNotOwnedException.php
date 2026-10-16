<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;

class BookingNotOwnedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cette réservation ne vous appartient pas.');
    }
}