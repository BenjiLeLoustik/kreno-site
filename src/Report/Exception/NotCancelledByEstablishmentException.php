<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class NotCancelledByEstablishmentException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Cette réservation n\'a pas été annulée par l\'établissement.')
        );
    }
}