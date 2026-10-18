<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class SlotUnavailableException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Trop tard, ce créneau vient d\'être réservé ou retiré.')
        );
    }
}