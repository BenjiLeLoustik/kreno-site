<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class AlreadyInstalledException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Le site est déjà installé : une version est en ligne.')
        );
    }
}