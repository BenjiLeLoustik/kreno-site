<?php

namespace App\Company\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class SignupEstablishmentRequiredException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Renseignez d\'abord votre établissement.')
        );
    }
}