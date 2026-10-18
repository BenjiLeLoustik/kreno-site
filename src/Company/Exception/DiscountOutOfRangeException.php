<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class DiscountOutOfRangeException extends BusinessRuleException
{
    public function __construct(int $min, int $max)
    {
        parent::__construct(
            Translation::translate('La remise doit être comprise entre {min} et {max} %.'),
            [
                'min' => $min,
                'max' => $max
            ],
        );
    }
}