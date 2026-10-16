<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;

class OwnSlotBookingException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Tu ne peux pas réserver un créneau de ton propre établissement.');
    }
}