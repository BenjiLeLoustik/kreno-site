<?php

namespace App\Billing\Exception;

use NeoPHP\Component\Http\Exception\HttpException;

class PaymentGatewayException extends HttpException
{
    public function __construct(string $message = 'Le prestataire de paiement ne répond pas. Réessayez dans quelques instants.', array $context = [])
    {
        parent::__construct(502, $message, [], $context);
    }
}