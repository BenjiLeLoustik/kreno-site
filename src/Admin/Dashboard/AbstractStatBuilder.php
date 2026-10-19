<?php

namespace App\Admin\Dashboard;

abstract class AbstractStatBuilder implements BuilderStatInterface
{
    public function getRole(): string
    {
        return 'ROLE_SUPPORT';
    }

    protected function plural(int $count, string $singular, string $plural): string
    {
        return $count > 1 ? $plural : $singular;
    }
}