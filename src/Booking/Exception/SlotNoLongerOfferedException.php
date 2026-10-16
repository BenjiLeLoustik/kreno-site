<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;

class SlotNoLongerOfferedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce créneau n\'est plus proposé.');
    }
}