<?php

namespace App\Report\Exception;

use App\Shared\Exception\BusinessRuleException;

class InvalidAttachmentException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('La pièce jointe doit être une image ou un PDF de moins de 5 Mo.');
    }
}