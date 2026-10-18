<?php

namespace App\Shared\Exception;

use App\Shared\Helper\Translation;
use Throwable;

class FileNotSavedException extends StorageException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            Translation::translate('Le fichier n\'a pas pu être enregistré.'),
            0,
            $previous
        );
    }
}