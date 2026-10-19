<?php

namespace App\Faq\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class FaqQuestionNotFoundException extends NotFoundHttpException
{
    public function __construct(int $id)
    {
        parent::__construct(
            Translation::translate('La question {id} n\'existe pas.'),
            [
                'id' => $id
            ]
        );
    }
}