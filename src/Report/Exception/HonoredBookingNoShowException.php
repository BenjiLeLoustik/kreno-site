<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class HonoredBookingNoShowException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            'Ce rendez-vous a été marqué comme honoré : il ne peut plus être déclaré en absence.',
        );
    }
}