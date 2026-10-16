<?php

namespace App\Log\Exception;

use RuntimeException;

class AppLogNotRecordedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Impossible d\'enregistrer l\'erreur.');
    }
}