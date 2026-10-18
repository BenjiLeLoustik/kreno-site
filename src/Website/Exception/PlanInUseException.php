<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class PlanInUseException extends BusinessRuleException
{
    public function __construct(string $code, string $plan)
    {
        parent::__construct(
            Translation::translate('La {code} ne peut pas être supprimée : des abonnements utilisent encore sa formule « {plan} ».'),
            [
                'code' => $code,
                'plan' => $plan
            ],
        );
    }
}