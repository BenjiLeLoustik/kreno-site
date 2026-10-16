<?php

namespace App\Billing\Exception;

use Throwable;

class PaymentGatewayUnreachableException extends PaymentGatewayException
{
    public function __construct(Throwable $previous)
    {
        parent::__construct(
            'Le prestataire de paiement est injoignable : {reason}',
            [
                'reason' => $previous->getMessage()
            ],
            $previous
        );
    }
}