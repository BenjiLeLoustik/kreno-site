<?php

namespace App\Shared\Exception;

use App\Shared\Helper\Translation;

class StoredFileNotFoundException extends StorageException
{
    public function __construct(string $path)
    {
        parent::__construct(
            sprintf(
                Translation::translate('Le fichier "%s" est introuvable.'),
                $path
            )
        );
    }
}