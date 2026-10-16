<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;

class SlotUnavailableException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Trop tard, ce créneau vient d\'être réservé ou retiré.');
    }
}