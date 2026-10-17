<?php

namespace App\Billing\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class PaymentMethodNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Moyen de paiement introuvable.');
    }
}