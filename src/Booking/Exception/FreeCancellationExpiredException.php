<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;

class FreeCancellationExpiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            'Le délai d\'annulation gratuite est dépassé : préviens directement l\'établissement.',
        );
    }
}