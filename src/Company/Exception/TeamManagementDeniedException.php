<?php

namespace App\Company\Exception;

use NeoPHP\Component\Http\Exception\AccessDeniedHttpException;

class TeamManagementDeniedException extends AccessDeniedHttpException
{
    public function __construct()
    {
        parent::__construct('Votre rôle ne permet pas de gérer l\'équipe.');
    }
}