<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;

class ForeignVersionItemException extends BusinessRuleException
{
    public function __construct(string $version)
    {
        parent::__construct(
            'Cet élément n\'appartient pas à la {version}.',
            [
                'version' => $version
            ]
        );
    }
}