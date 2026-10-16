<?php

namespace App\User\Exception;

use NeoPHP\Package\Security\Exception\CustomUserMessageAuthenticationException;

class InvalidCredentialsException extends CustomUserMessageAuthenticationException
{
    public function __construct()
    {
        parent::__construct('Identifiants invalides.');
    }
}