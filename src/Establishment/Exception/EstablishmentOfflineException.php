<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class EstablishmentOfflineException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('L\'établissement doit être en ligne pour publier un créneau.');
    }
}