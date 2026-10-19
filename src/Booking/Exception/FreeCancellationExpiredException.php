<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class FreeCancellationExpiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Le délai d\'annulation gratuite est dépassé : préviens directement l\'établissement.'),
        );
    }
}