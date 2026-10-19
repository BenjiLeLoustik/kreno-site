<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class IdentityRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Renseigne le nom du site, le domaine, l\'accroche, la description et l\'e-mail de contact.'),
        );
    }
}