<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;

class SlotTooCloseException extends BusinessRuleException
{
    public function __construct(int $minutes)
    {
        parent::__construct(
            'Ce créneau commence dans moins de {minutes} min : il ne peut plus être réservé.',
            [
                'minutes' => $minutes
            ],
        );
    }
}