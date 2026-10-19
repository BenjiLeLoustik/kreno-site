<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class PriceChangeNoticeTooShortException extends BusinessRuleException
{
    public function __construct(string $code, int $count, int $days)
    {
        parent::__construct(
            Translation::translate('La {code} augmente le prix de {count} abonnement(s) : sa mise en ligne doit être planifiée au moins {days} jours à l\'avance pour respecter le préavis.'),
            [
                'code' => $code,
                'count' => $count,
                'days' => $days
            ],
        );
    }
}