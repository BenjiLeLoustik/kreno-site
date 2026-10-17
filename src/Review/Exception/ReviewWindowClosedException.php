<?php

namespace App\Review\Exception;

use App\Shared\Exception\BusinessRuleException;

class ReviewWindowClosedException extends BusinessRuleException
{
    public function __construct(int $days)
    {
        parent::__construct(
            'Un avis peut être donné dans les {days} jours qui suivent le rendez-vous.',
            ['days' => $days],
        );
    }
}