<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class SlotDiscountOutOfRangeException extends BusinessRuleException
{
    public function __construct(int $min, int $max)
    {
        parent::__construct(
            'La remise doit être comprise entre {min} % et {max} %.',
            [
                'min' => $min,
                'max' => $max
            ],
        );
    }
}