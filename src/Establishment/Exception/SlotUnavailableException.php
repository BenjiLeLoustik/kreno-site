<?php

namespace App\Establishment\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class SlotUnavailableException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Ce créneau n\'est plus disponible.')
        );
    }
}