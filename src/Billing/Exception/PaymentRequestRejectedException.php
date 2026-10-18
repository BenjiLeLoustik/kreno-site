<?php

namespace App\Billing\Exception;

use App\Shared\Helper\Translation;

class PaymentRequestRejectedException extends PaymentGatewayException
{
    public function __construct(?string $reason = null)
    {
        parent::__construct(
            Translation::translate('Le prestataire de paiement a refusé la requête : {reason}'),
            [
                'reason' => $reason ?? 'erreur inconnue'
            ]
        );
    }
}