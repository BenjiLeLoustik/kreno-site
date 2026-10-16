<?php

namespace App\Shared\Exception;

use Throwable;

class FileNotSavedException extends StorageException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Le fichier n\'a pas pu être enregistré.', 0, $previous);
    }
}