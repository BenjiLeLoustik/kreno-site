<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class SlotTooCloseException extends BusinessRuleException
{
    public function __construct(int $minutes)
    {
        parent::__construct(
            Translation::translate('Ce créneau commence dans moins de {minutes} min : il ne peut plus être réservé.'),
            [
                'minutes' => $minutes
            ],
        );
    }
}