<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class AdminEmailExistsException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Un compte existe déjà avec cette adresse : connecte-toi avec ce compte puis reviens sur l\'installation.'),
        );
    }
}