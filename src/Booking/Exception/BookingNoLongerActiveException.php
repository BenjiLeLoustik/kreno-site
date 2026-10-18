<?php

namespace App\Booking\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class BookingNoLongerActiveException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Cette réservation n\'est plus active.')
        );
    }
}