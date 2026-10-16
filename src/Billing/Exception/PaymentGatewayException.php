<?php

namespace App\Billing\Exception;

use NeoPHP\Component\Http\Exception\HttpException;
use Throwable;

class PaymentGatewayException extends HttpException
{
    public function __construct(
        string $message = 'Le prestataire de paiement ne répond pas. Réessayez dans quelques instants.',
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct(502, $message, [], $context, $previous);
    }
}