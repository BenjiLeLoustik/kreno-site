<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class ReplyNotExpectedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce signalement n\'attend pas de réponse de votre part.');
    }
}