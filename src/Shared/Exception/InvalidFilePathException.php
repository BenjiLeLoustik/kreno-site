<?php

namespace App\Shared\Exception;

use App\Shared\Helper\Translation;

class InvalidFilePathException extends StorageException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Chemin de fichier invalide.')
        );
    }
}