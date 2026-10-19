<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class OfferUnavailableException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Cette offre n\'est plus disponible.')
        );
    }
}