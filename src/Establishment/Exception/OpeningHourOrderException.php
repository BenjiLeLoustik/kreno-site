<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class OpeningHourOrderException extends BusinessRuleException
{
    public function __construct(string $day)
    {
        parent::__construct(
            Translation::translate('{day} : l\'heure d\'ouverture doit précéder l\'heure de fermeture.'),
            [
                'day' => $day
            ],
        );
    }
}