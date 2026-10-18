<?php

namespace App\User\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Package\Security\Exception\CustomUserMessageAuthenticationException;

class InvalidCredentialsException extends CustomUserMessageAuthenticationException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Identifiants invalides.')
        );
    }
}