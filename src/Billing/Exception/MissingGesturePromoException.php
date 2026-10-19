<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class MissingGesturePromoException extends BusinessRuleException
{
    public function __construct(string $code)
    {
        parent::__construct(
            Translation::translate('Créez d\'abord le code {code} dans « Tarifs et abonnements ».'),
            [
                'code' => $code
            ]
        );
    }
}