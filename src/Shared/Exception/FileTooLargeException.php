<?php

namespace App\Shared\Exception;

use App\Shared\Helper\Translation;

class FileTooLargeException extends StorageException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Le fichier est trop volumineux.')
        );
    }
}