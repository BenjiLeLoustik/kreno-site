<?php

namespace App\Shared\Exception;

use App\Shared\Helper\Translation;

class FileTypeNotAcceptedException extends StorageException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Ce type de fichier n\'est pas accepté.')
        );
    }
}