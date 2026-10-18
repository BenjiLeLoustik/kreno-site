<?php

namespace App\Catalog\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class ServiceSuggestionNotFoundException extends NotFoundHttpException
{
    public function __construct(int $id)
    {
        parent::__construct(
            Translation::translate('La suggestion {id} n\'existe pas.'),
            [
                'id' => $id
            ]
        );
    }
}