<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class BookingAlreadyReportedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            'Tu as déjà signalé un problème sur cette réservation : ajoute un message à ton signalement.',
        );
    }
}