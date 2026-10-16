<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class DuplicateSlotException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce créneau est déjà publié.');
    }
}