<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class WithdrawNotAllowedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Seul un créneau en ligne peut être retiré.')
        );
    }
}