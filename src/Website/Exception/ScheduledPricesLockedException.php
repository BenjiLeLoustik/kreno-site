<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class ScheduledPricesLockedException extends BusinessRuleException
{
    public function __construct(string $code)
    {
        parent::__construct(
            'La {code} est planifiée et ses changements de prix ont déjà été annoncés aux abonnés. Annulez la planification pour modifier les formules, puis replanifiez-la : seules les différences seront envoyées.',
            [
                'code' => $code
            ],
        );
    }
}