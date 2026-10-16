<?php

namespace App\Shared\Exception;

class FileTooLargeException extends StorageException
{
    public function __construct()
    {
        parent::__construct('Le fichier est trop volumineux.');
    }
}