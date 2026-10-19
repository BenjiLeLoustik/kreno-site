<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class ScheduledPricesLockedException extends BusinessRuleException
{
    public function __construct(string $code)
    {
        parent::__construct(
            Translation::translate('La {code} est planifiée et ses changements de prix ont déjà été annoncés aux abonnés. Annulez la planification pour modifier les formules, puis replanifiez-la : seules les différences seront envoyées.'),
            [
                'code' => $code
            ],
        );
    }
}