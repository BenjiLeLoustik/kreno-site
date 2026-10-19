<?php

namespace App\Catalog\Exception;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class CategoryNotFoundException extends NotFoundHttpException
{
    public function __construct(string $slug)
    {
        parent::__construct(
            Translation::translate('La catégorie {slug} n\'existe pas.'),
            [
                'slug' => $slug
            ]
        );
    }
}