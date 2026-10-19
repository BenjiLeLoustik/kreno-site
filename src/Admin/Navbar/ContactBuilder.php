<?php

namespace App\Admin\Navbar;

use App\Repository\AppContactRepository;
use App\Shared\Helper\Translation;
use App\Support\Enum\AppContactStatus;

class ContactBuilder extends AbstractNavbarBuilder
{
    public function __construct(
        private AppContactRepository $contacts,
    ) {
    }

    public function getKey(): string
    {
        return 'contacts';
    }

    public function getLabel(): string
    {
        return Translation::translate('Messages reçus');
    }

    public function getRoute(): string
    {
        return 'admin_contacts';
    }

    public function getRole(): string
    {
        return 'ROLE_SUPPORT';
    }

    public function getIcon(): string
    {
        return 'mail';
    }

    public function getPosition(): int
    {
        return 40;
    }

    public function getBadge(): int
    {
        return $this->contacts->countByStatus(AppContactStatus::NEW);
    }
}