<?php

namespace App\Shared\Exception;

class FileTypeNotAcceptedException extends StorageException
{
    public function __construct()
    {
        parent::__construct('Ce type de fichier n\'est pas accepté.');
    }
}