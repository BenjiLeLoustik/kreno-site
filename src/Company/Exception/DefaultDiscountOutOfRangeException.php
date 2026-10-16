<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class DefaultDiscountOutOfRangeException extends BusinessRuleException
{
    public function __construct(int $min, int $max)
    {
        parent::__construct(
            'La remise par défaut doit être comprise entre {min} et {max} %.',
            [
                'min' => $min,
                'max' => $max
            ],
        );
    }
}