<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class SlotTooSoonException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce créneau commence trop tôt : il serait retiré avant de pouvoir être réservé.');
    }
}