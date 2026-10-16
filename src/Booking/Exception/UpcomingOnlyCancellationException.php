<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;

class UpcomingOnlyCancellationException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Seul un rendez-vous à venir peut être annulé.');
    }
}