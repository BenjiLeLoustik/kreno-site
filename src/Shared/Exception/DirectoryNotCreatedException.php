<?php

namespace App\Shared\Exception;

class DirectoryNotCreatedException extends StorageException
{
    public function __construct(string $directory)
    {
        parent::__construct(sprintf('Le dossier "%s" n\'a pas pu être créé.', $directory));
    }
}