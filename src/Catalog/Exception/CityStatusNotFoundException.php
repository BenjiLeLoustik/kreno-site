<?php

namespace App\Catalog\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class CityStatusNotFoundException extends NotFoundHttpException
{
    public function __construct(string $status)
    {
        parent::__construct(
            Translation::translate('Statut {status} inconnu.'),
            [
                'status' => $status
            ]
        );
    }
}