<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class RemovalTooLateException extends BusinessRuleException
{
    public function __construct(int $minutes)
    {
        parent::__construct(
            'Un créneau est retiré au plus tard {minutes} min avant son début.',
            [
                'minutes' => $minutes
            ],
        );
    }
}