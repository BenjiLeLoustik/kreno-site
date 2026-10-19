<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class ForeignVersionItemException extends BusinessRuleException
{
    public function __construct(string $version)
    {
        parent::__construct(
            Translation::translate('Cet élément n\'appartient pas à la {version}.'),
            [
                'version' => $version
            ]
        );
    }
}