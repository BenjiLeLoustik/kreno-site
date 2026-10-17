<?php

namespace App\Website\Exception;

use NeoPHP\Component\Http\Exception\NotFoundHttpException;

class WebsiteVersionNotFoundException extends NotFoundHttpException
{
    public const ONLINE = 'en ligne';

    public function __construct(?string $code)
    {
        parent::__construct('La version {code} n\'existe pas.', ['code' => $code ?? self::ONLINE]);
    }
}