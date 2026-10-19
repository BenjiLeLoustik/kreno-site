<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class AlertLimitReachedException extends BusinessRuleException
{
    public function __construct(int $max)
    {
        parent::__construct(
            Translation::translate('Vous avez déjà {max} alertes : supprimez-en une pour en créer une nouvelle.'),
            [
                'max' => $max
            ],
        );
    }
}