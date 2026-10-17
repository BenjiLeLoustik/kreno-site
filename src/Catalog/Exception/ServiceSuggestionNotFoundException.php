<?php

namespace App\Catalog\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class ServiceSuggestionNotFoundException extends NotFoundHttpException
{
    public function __construct(int $id)
    {
        parent::__construct('La suggestion {id} n\'existe pas.', ['id' => $id]);
    }
}