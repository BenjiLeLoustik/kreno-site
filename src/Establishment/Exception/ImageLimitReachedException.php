<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class ImageLimitReachedException extends BusinessRuleException
{
    public function __construct(int $max)
    {
        parent::__construct(
            Translation::translate('Vous pouvez ajouter jusqu\'à {max} photos.'),
            [
                'max' => $max
            ]
        );
    }
}