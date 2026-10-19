<?php

namespace App\Establishment\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class PublicEstablishmentNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Cet établissement n\'existe pas ou n\'est plus en ligne.')
        );
    }
}