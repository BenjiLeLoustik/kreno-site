<?php

namespace App\Shared\Exception;

class StoredFileNotFoundException extends StorageException
{
    public function __construct(string $path)
    {
        parent::__construct(sprintf('Le fichier "%s" est introuvable.', $path));
    }
}