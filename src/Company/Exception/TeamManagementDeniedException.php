<?php

namespace App\Company\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\AccessDeniedHttpException;

class TeamManagementDeniedException extends AccessDeniedHttpException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Votre rôle ne permet pas de gérer l\'équipe.')
        );
    }
}