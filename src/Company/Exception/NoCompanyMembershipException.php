<?php

namespace App\Company\Exception;

use NeoPHP\Component\Http\Exception\AccessDeniedHttpException;

class NoCompanyMembershipException extends AccessDeniedHttpException
{
    public function __construct()
    {
        parent::__construct('Votre compte n\'est rattaché à aucune entreprise.');
    }
}