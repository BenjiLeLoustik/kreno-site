<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class SlotTooSoonException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Ce créneau commence trop tôt : il serait retiré avant de pouvoir être réservé.')
        );
    }
}