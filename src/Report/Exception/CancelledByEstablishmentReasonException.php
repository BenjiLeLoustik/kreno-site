<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class CancelledByEstablishmentReasonException extends BusinessRuleException
{
    public function __construct(string $reason)
    {
        parent::__construct(
            Translation::translate('Cette réservation a été annulée par l\'établissement : choisis « {reason} ».'),
            [
                'reason' => $reason
            ],
        );
    }
}