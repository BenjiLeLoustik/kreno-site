<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;

class AlertLimitReachedException extends BusinessRuleException
{
    public function __construct(int $max)
    {
        parent::__construct(
            'Vous avez déjà {max} alertes : supprimez-en une pour en créer une nouvelle.',
            [
                'max' => $max
            ],
        );
    }
}