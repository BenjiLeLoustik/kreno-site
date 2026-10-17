<?php

namespace App\Billing\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class InvoiceNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Facture introuvable.');
    }
}