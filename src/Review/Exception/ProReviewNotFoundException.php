<?php

namespace App\Review\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class ProReviewNotFoundException extends NotFoundHttpException
{
    public function __construct(int $id)
    {
        parent::__construct(
            Translation::translate('L\'avis {id} n\'existe pas pour votre établissement.', ['id' => $id]),
            [
                'id' => $id
            ]
        );
    }
}