<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class ReportEstablishmentNotFoundException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Établissement introuvable.')
        );
    }
}