<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class IdentityRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            'Renseigne le nom du site, le domaine, l\'accroche, la description et l\'e-mail de contact.',
        );
    }
}