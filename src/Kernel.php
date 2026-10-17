<?php

declare(strict_types=1);

namespace App;

use App\Shared\Provider\TranslationProvider;
use NeoPHP\Component\Kernel\Contract\AbstractKernel;

class Kernel extends AbstractKernel
{
    protected function providers(): iterable
    {
        return [
            TranslationProvider::class,
        ];
    }
}