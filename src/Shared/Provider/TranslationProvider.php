<?php

namespace App\Shared\Provider;

use App\Shared\Helper\Translation;
use NeoPHP\Component\Container\ContainerManagerInterface;
use NeoPHP\Component\Container\Contract\AbstractProvider;

class TranslationProvider extends AbstractProvider
{
    public function register(ContainerManagerInterface $container): void
    {
    }

    public function boot(ContainerManagerInterface $container): void
    {
        Translation::boot($container->get(Translation::class));
    }
}