<?php

namespace App\Billing\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class InvalidWebhookSignatureException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Signature de notification invalide.')
        );
    }
}