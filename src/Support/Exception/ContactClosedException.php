<?php

namespace App\Support\Exception;

use App\Shared\Exception\BusinessRuleException;

class ContactClosedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct(
            'Cette demande est fermée. Écrivez-nous depuis la page d\'aide pour en ouvrir une nouvelle.',
        );
    }
}