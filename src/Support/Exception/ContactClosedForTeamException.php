<?php

namespace App\Support\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class ContactClosedForTeamException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Cette demande est fermée : rouvrez-la pour répondre.')
        );
    }
}