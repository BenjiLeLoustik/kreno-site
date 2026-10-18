<?php

namespace App\Billing\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\HttpException;
use Throwable;

class PaymentGatewayException extends HttpException
{
    public function __construct(
        ?string $message = null,
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct(502, $message ?? $this->defaultMessage(), [], $context, $previous);
    }

    private function defaultMessage(): string
    {
        return Translation::translate(
            'Le prestataire de paiement ne répond pas. Réessayez dans quelques instants.'
        );
    }
}