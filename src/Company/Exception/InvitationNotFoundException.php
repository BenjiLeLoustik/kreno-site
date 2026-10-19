<?php

namespace App\Company\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class InvitationNotFoundException extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct(
            Translation::translate('Invitation introuvable.')
        );
    }
}