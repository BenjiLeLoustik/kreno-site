<?php

namespace App\Review\Exception;

use App\Shared\Exception\BusinessRuleException;

class BookingNotReviewableException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Tu pourras donner ton avis une fois ton rendez-vous honoré.');
    }
}