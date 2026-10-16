<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;

class BookingNotStartedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Le rendez-vous n\'a pas encore commencé.');
    }
}