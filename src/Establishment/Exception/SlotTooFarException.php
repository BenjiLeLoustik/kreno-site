<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class SlotTooFarException extends BusinessRuleException
{
    public function __construct(int $days)
    {
        parent::__construct(
            'Un créneau se publie au plus {days} jours à l\'avance.',
            [
                'days' => $days
            ]
        );
    }
}