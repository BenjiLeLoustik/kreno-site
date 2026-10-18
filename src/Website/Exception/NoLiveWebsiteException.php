<?php

namespace App\Website\Exception;

use App\Shared\Helper\Translation;

class NoLiveWebsiteException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Aucune version du site n\'est en ligne.')
        );
    }
}