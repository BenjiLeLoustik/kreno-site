<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class CityNotOpenException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cette ville n\'est pas encore ouverte sur Kréno.');
    }
}