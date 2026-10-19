<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class BookingAlreadyReportedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Tu as déjà signalé un problème sur cette réservation : ajoute un message à ton signalement.'),
        );
    }
}