<?php

namespace App\User\Exception;

use NeoPHP\Package\Security\Exception\CustomUserMessageAuthenticationException;

class AccountClosedException extends CustomUserMessageAuthenticationException
{
    public function __construct()
    {
        parent::__construct('Votre compte a été fermé. Contactez aide@kreno.fr pour en savoir plus.');
    }
}