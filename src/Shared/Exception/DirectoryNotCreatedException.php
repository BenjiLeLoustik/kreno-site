<?php

namespace App\Shared\Exception;

use App\Shared\Helper\Translation;

class DirectoryNotCreatedException extends StorageException
{
    public function __construct(string $directory)
    {
        parent::__construct(
            sprintf(Translation::translate('Le dossier "%s" n\'a pas pu être créé.'), $directory)
        );
    }
}