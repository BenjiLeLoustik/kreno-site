<?php

namespace App\Admin\Navbar;

use App\Admin\Navbar\AbstractNavbarBuilder;
use App\Shared\Helper\Translation;

class FaqBuilder extends AbstractNavbarBuilder
{

    public function getKey(): string
    {
        return 'faq';
    }

    public function getLabel(): string
    {
        return Translation::translate('FAQ');
    }

    public function getRoute(): string
    {
        return 'admin_faq_index';
    }

    public function getIcon(): string
    {
        return 'circle-help';
    }

    public function getPosition(): int
    {
        return 95;
    }
}