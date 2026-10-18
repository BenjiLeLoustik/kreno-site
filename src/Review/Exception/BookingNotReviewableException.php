<?php

namespace App\Review\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class BookingNotReviewableException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Tu pourras donner ton avis une fois ton rendez-vous honoré.')
        );
    }
}