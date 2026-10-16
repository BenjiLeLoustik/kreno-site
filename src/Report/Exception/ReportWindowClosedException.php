<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class ReportWindowClosedException extends BusinessRuleException
{
    public function __construct(int $hours)
    {
        parent::__construct(
            'Le signalement est possible dans les {hours} h qui suivent le rendez-vous.',
            [
                'hours' => $hours
            ],
        );
    }
}