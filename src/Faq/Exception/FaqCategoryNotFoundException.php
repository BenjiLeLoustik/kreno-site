<?php

namespace App\Faq\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class FaqCategoryNotFoundException extends NotFoundHttpException
{
    public function __construct(string $slug)
    {
        parent::__construct('La rubrique {slug} n\'existe pas.', ['slug' => $slug]);
    }
}