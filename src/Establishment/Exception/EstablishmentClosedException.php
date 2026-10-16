<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class EstablishmentClosedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('L\'établissement est fermé ce jour-là.');
    }
}