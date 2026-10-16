<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;

class UpcomingBookingLimitException extends BusinessRuleException
{
    public function __construct(int $max)
    {
        parent::__construct(
            'Tu as déjà {max} réservation(s) à venir : c\'est le maximum autorisé.',
            [
                'max' => $max
            ],
        );
    }
}