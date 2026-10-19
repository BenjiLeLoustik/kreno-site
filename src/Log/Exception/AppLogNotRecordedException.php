<?php

namespace App\Log\Exception;

use App\Shared\Helper\Translation;
use RuntimeException;

class AppLogNotRecordedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Impossible d\'enregistrer l\'erreur.')
        );
    }
}