<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class OwnSlotBookingException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Tu ne peux pas réserver un créneau de ton propre établissement.')
        );
    }
}