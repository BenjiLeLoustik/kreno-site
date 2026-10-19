<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class SelfSanctionException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Vous ne pouvez pas vous sanctionner vous-même.')
        );
    }
}