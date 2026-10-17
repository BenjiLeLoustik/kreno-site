<?php

namespace App\Company\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class EstablishmentInaccessibleException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Établissement inaccessible.');
    }
}