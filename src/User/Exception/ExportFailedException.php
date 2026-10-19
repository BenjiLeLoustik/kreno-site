<?php

namespace App\User\Exception;

use App\Shared\Exception\StorageException;
use App\Shared\Helper\Translation;

class ExportFailedException extends StorageException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('L\'export n\'a pas pu être préparé.')
        );
    }
}