<?php

namespace App\Company\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class CompanyNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Entreprise introuvable.')
        );
    }
}