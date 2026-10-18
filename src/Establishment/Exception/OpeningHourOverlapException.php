<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class OpeningHourOverlapException extends BusinessRuleException
{
    public function __construct(string $day)
    {
        parent::__construct(
            Translation::translate('{day} : deux plages horaires se chevauchent.'),
            [
                'day' => $day
            ]
        );
    }
}