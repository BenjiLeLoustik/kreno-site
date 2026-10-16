<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class InvalidImageException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('La photo doit être au format JPEG, PNG ou WebP et peser moins de 8 Mo.');
    }
}