<?php

namespace App\Shared\Exception;

use App\Shared\Helper\Translation;

class UploadNotReceivedException extends StorageException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Le fichier n\'a pas pu être reçu.')
        );
    }
}