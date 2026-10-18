<?php

namespace App\Shared\Exception;

use App\Shared\Helper\Translation;

class FileNotWrittenException extends StorageException
{
    public function __construct(string $path)
    {
        parent::__construct(
            sprintf(
                Translation::translate('Le fichier "%s" n\'a pas pu être écrit.'),
                $path
            )
        );
    }
}