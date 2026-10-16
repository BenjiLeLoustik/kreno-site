<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;

class EstablishmentLimitReachedException extends BusinessRuleException
{
    public function __construct(string $plan, int $max)
    {
        parent::__construct(
            'Votre formule {plan} est limitée à {max} établissement(s). Passez à une formule supérieure pour en ajouter.',
            [
                'plan' => $plan,
                'max' => $max
            ],
        );
    }
}