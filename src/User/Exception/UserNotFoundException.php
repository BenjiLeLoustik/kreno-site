<?php

namespace App\User\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class UserNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Utilisateur introuvable.');
    }
}