<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class AutoRemovalTooLateException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Le retrait automatique doit arriver avant la fin de l\'annulation gratuite.');
    }
}