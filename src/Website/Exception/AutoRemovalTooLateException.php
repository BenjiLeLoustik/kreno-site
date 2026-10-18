<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class AutoRemovalTooLateException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Le retrait automatique doit arriver avant la fin de l\'annulation gratuite.')
        );
    }
}