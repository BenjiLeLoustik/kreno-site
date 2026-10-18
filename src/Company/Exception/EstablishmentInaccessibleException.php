<?php

namespace App\Company\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class EstablishmentInaccessibleException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Établissement inaccessible.')
        );
    }
}