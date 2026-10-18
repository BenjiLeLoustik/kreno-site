<?php

namespace App\Company\Navbar;

use App\Company\Security\Permission;
use App\Shared\Helper\Translation;

class PublishBuilder extends AbstractProNavbarBuilder
{
    public function getKey(): string
    {
        return 'publish';
    }

    public function getLabel(): string
    {
        return Translation::translate('Publier un créneau');
    }

    public function getRoute(): string
    {
        return 'pro_slot_new';
    }

    public function getIcon(): string
    {
        return 'circle-plus';
    }

    public function getPosition(): int
    {
        return 20;
    }

    public function getPermission(): ?string
    {
        return Permission::SLOTS_MANAGE;
    }
}