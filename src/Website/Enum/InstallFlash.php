<?php

namespace App\Website\Enum;

enum InstallFlash
{
    case DONE;

    public function text(): string
    {
        return match ($this) {
            self::DONE => 'Installation terminée : la première version du site est en ligne.',
        };
    }
}