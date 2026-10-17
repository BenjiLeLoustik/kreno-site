<?php

namespace App\Review\Exception;

use App\Shared\Exception\BusinessRuleException;

class BookingAlreadyReviewedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Tu as déjà donné ton avis sur ce rendez-vous.');
    }
}