<?php

namespace App\Establishment\Exception;

use App\Shared\Exception\BusinessRuleException;

class ImageLimitReachedException extends BusinessRuleException
{
    public function __construct(int $max)
    {
        parent::__construct('Vous pouvez ajouter jusqu\'à {max} photos.', ['max' => $max]);
    }
}