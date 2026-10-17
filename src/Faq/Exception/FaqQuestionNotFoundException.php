<?php

namespace App\Faq\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class FaqQuestionNotFoundException extends NotFoundHttpException
{
    public function __construct(int $id)
    {
        parent::__construct('La question {id} n\'existe pas.', ['id' => $id]);
    }
}