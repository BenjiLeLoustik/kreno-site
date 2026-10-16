<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class CityUnavailableException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Kréno n\'est pas encore disponible dans cette ville.');
    }
}