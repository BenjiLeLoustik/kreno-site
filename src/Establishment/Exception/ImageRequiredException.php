<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class ImageRequiredException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Choisissez une photo à envoyer.');
    }
}