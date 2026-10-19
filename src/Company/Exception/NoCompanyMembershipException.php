<?php

namespace App\Company\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\AccessDeniedHttpException;

class NoCompanyMembershipException extends AccessDeniedHttpException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Votre compte n\'est rattaché à aucune entreprise.')
        );
    }
}