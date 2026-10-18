<?php

namespace App\User\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Package\Security\Exception\CustomUserMessageAuthenticationException;

class AccountClosedException extends CustomUserMessageAuthenticationException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Votre compte a été fermé. Contactez aide@kreno.fr pour en savoir plus.')
        );
    }
}