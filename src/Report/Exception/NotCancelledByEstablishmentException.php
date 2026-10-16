<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class NotCancelledByEstablishmentException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cette réservation n\'a pas été annulée par l\'établissement.');
    }
}