<?php

namespace App\Shared\Exception;

class InvalidFilePathException extends StorageException
{
    public function __construct()
    {
        parent::__construct('Chemin de fichier invalide.');
    }
}