<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class HonoredBookingNoShowException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Ce rendez-vous a été marqué comme honoré : il ne peut plus être déclaré en absence.'),
        );
    }
}