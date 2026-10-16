<?php

namespace App\Booking\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class CustomerBookingNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Réservation introuvable.');
    }
}