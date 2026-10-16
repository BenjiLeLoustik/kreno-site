<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;

class BookingAlreadyClosedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce rendez-vous est déjà clôturé.');
    }
}