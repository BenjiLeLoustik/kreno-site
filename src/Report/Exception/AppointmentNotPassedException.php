<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class AppointmentNotPassedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Vous pourrez signaler ce client une fois l\'heure du rendez-vous passée.')
        );
    }
}