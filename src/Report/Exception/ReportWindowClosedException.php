<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class ReportWindowClosedException extends BusinessRuleException
{
    public function __construct(int $hours)
    {
        parent::__construct(
            Translation::translate('Le signalement est possible dans les {hours} h qui suivent le rendez-vous.'),
            [
                'hours' => $hours
            ],
        );
    }
}