<?php

namespace App\Establishment\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class PublicEstablishmentNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Cet établissement n\'existe pas ou n\'est plus en ligne.');
    }
}