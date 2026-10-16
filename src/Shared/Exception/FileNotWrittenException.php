<?php

namespace App\Shared\Exception;

class FileNotWrittenException extends StorageException
{
    public function __construct(string $path)
    {
        parent::__construct(sprintf('Le fichier "%s" n\'a pas pu être écrit.', $path));
    }
}