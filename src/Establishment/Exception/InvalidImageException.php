<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class InvalidImageException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('La photo doit être au format JPEG, PNG ou WebP et peser moins de 8 Mo.')
        );
    }
}