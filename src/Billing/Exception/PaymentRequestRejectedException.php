<?php

namespace App\Billing\Exception;

class PaymentRequestRejectedException extends PaymentGatewayException
{
    public function __construct(?string $reason = null)
    {
        parent::__construct(
            'Le prestataire de paiement a refusé la requête : {reason}',
            [
                'reason' => $reason ?? 'erreur inconnue'
            ]
        );
    }
}