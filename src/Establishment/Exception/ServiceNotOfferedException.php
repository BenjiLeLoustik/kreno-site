<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class ServiceNotOfferedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Cette prestation n\'existe pas ou n\'est plus proposée.')
        );
    }
}