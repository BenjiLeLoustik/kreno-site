<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class BookingNotOwnedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Cette réservation ne vous appartient pas.')
        );
    }
}