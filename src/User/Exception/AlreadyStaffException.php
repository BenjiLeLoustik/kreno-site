<?php

namespace App\User\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class AlreadyStaffException extends BusinessRuleException
{
    public function __construct(string $email)
    {
        parent::__construct(
            Translation::translate('{email} fait déjà partie de l\'équipe Kréno.'),
            [
                'email' => $email
            ]
        );
    }
}