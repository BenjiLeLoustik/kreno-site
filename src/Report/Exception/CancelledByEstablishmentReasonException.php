<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class CancelledByEstablishmentReasonException extends BusinessRuleException
{
    public function __construct(string $reason)
    {
        parent::__construct(
            'Cette réservation a été annulée par l\'établissement : choisis « {reason} ».',
            [
                'reason' => $reason
            ],
        );
    }
}