<?php

namespace App\Booking\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class BookingNoLongerActiveException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Cette réservation n\'est plus active.');
    }
}