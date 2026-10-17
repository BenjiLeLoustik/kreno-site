<?php

namespace App\Company\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class SignupEstablishmentRequiredException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Renseignez d\'abord votre établissement.');
    }
}