<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class SlotTooFarException extends BusinessRuleException
{
    public function __construct(int $days)
    {
        parent::__construct(
            Translation::translate('Un créneau se publie au plus {days} jours à l\'avance.'),
            [
                'days' => $days
            ]
        );
    }
}