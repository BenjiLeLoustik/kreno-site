<?php

namespace App\Company\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class NoAccessibleEstablishmentException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Aucun établissement accessible.')
        );
    }
}