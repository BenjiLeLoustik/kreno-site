<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class MissingAttachmentException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ce signalement n\'a pas de pièce jointe.');
    }
}