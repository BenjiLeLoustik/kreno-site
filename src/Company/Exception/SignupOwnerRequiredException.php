<?php

namespace App\Company\Exception;

use NeoPHP\Component\Http\Exception\AccessDeniedHttpException;

class SignupOwnerRequiredException extends AccessDeniedHttpException
{
    public function __construct()
    {
        parent::__construct('Forbidden');
    }
}