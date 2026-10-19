<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class BookingNotStartedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Le rendez-vous n\'a pas encore commencé.')
        );
    }
}