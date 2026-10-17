<?php

namespace App\Billing\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class PaymentNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Paiement introuvable.');
    }
}