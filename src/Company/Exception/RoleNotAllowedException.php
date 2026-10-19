<?php

namespace App\Company\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\AccessDeniedHttpException;

class RoleNotAllowedException extends AccessDeniedHttpException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Votre rôle ne permet pas cette action.')
        );
    }
}