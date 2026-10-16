<?php

namespace App\Catalog\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class CategoryNotFoundException extends NotFoundHttpException
{
    public function __construct(string $slug)
    {
        parent::__construct(
            'La catégorie {slug} n\'existe pas.',
            [
                'slug' => $slug
            ]
        );
    }
}