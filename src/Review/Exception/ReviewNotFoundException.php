<?php

namespace App\Review\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class ReviewNotFoundException extends NotFoundHttpException
{
    public function __construct(int $id)
    {
        parent::__construct('L\'avis {id} n\'existe pas.', ['id' => $id]);
    }
}