<?php

namespace App\Faq\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class FaqCategoryNotFoundException extends NotFoundHttpException
{
    public function __construct(string $slug)
    {
        parent::__construct(
            Translation::translate('La rubrique {slug} n\'existe pas.'),
            [
                'slug' => $slug
            ]
        );
    }
}