<?php

namespace App\User\Navbar;

use App\Shared\Helper\Translation;

class MessageBuilder extends AbstractAccountNavbarBuilder
{
    public function getKey(): string
    {
        return 'messages';
    }

    public function getLabel(): string
    {
        return Translation::translate('Mes messages');
    }

    public function getRoute(): string
    {
        return 'account_messages';
    }

    public function getIcon(): string
    {
        return 'message-circle';
    }

    public function getPosition(): int
    {
        return 70;
    }
}