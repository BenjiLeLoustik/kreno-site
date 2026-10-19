<?php

namespace App\Billing\Exception;

use App\Shared\Helper\Translation;
use Throwable;

class PaymentGatewayUnreachableException extends PaymentGatewayException
{
    public function __construct(Throwable $previous)
    {
        parent::__construct(
            Translation::translate('Le prestataire de paiement est injoignable : {reason}'),
            [
                'reason' => $previous->getMessage()
            ],
            $previous
        );
    }
}