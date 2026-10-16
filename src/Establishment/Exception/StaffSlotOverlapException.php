<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class StaffSlotOverlapException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce membre a déjà un créneau sur cet horaire.');
    }
}