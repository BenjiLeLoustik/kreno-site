<?php

namespace App\Company\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class NoAccessibleEstablishmentException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Aucun établissement accessible.');
    }
}