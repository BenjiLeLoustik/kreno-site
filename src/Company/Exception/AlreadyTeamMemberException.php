<?php

namespace App\Company\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class AlreadyTeamMemberException extends BusinessRuleException
{
    public function __construct(string $email)
    {
        parent::__construct(
            Translation::translate('{email} fait déjà partie de l\'équipe.'),
            [
                'email' => $email
            ],
        );
    }
}