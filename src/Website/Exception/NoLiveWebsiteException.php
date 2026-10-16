<?php

namespace App\Website\Exception;

class NoLiveWebsiteException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Aucune version du site n\'est en ligne.');
    }
}