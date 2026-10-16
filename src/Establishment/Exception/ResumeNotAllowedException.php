<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class ResumeNotAllowedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Seul un établissement suspendu peut être remis en ligne.');
    }
}