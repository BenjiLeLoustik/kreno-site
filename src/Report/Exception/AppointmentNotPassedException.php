<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class AppointmentNotPassedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Vous pourrez signaler ce client une fois l\'heure du rendez-vous passée.');
    }
}