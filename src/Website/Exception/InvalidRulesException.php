<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class InvalidRulesException extends BusinessRuleException
{
    public const MIN_ABOVE_MAX = 'la remise minimale doit être inférieure à la maximale';

    public const DEFAULT_OUT_OF_RANGE = 'la remise par défaut doit être comprise entre la minimale et la maximale';

    public const AUTO_REMOVAL_TOO_LATE = 'le retrait automatique doit arriver avant la fin de l\'annulation gratuite';

    public function __construct(array $errors)
    {
        parent::__construct(
            'Règles invalides : {errors}.',
            [
                'errors' => implode(' ; ', $errors)
            ]
        );
    }
}