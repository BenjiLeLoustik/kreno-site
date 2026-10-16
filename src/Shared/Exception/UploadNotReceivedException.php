<?php

namespace App\Shared\Exception;

class UploadNotReceivedException extends StorageException
{
    public function __construct()
    {
        parent::__construct('Le fichier n\'a pas pu être reçu.');
    }
}