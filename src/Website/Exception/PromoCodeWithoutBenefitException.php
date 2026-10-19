<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class PromoCodeWithoutBenefitException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Un code promo doit donner une remise ou des mois offerts.')
        );
    }
}