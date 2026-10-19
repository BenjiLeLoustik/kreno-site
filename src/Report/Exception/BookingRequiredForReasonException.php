<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class BookingRequiredForReasonException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Ce motif concerne une réservation : choisis-la.')
        );
    }
}