<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class GuestDetailsRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Indique ton prénom, ton téléphone et ton adresse e-mail, ou connecte-toi.')
        );
    }
}