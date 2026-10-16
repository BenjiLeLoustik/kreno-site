<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class OpeningHourOverlapException extends BusinessRuleException
{
    public function __construct(string $day)
    {
        parent::__construct(
            '{day} : deux plages horaires se chevauchent.',
            [
                'day' => $day
            ]
        );
    }
}