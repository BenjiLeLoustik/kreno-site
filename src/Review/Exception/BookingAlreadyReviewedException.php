<?php

namespace App\Review\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class BookingAlreadyReviewedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Tu as déjà donné ton avis sur ce rendez-vous.')
        );
    }
}