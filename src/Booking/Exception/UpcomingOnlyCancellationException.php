<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class UpcomingOnlyCancellationException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Seul un rendez-vous à venir peut être annulé.')
        );
    }
}