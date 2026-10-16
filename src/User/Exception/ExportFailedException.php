<?php

namespace App\User\Exception;

use App\Shared\Exception\StorageException;

class ExportFailedException extends StorageException
{
    public function __construct()
    {
        parent::__construct('L\'export n\'a pas pu être préparé.');
    }
}