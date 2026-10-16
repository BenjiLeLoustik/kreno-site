<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class InvalidSlotDateException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Le jour ou l\'heure du créneau est invalide.');
    }
}