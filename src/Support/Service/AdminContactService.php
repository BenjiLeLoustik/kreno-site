<?php

namespace App\Support\Service;

use App\Entity\AppContact;
use App\Entity\User\User;

class AdminContactService
{
    public function __construct(
        private ContactService $contacts,
    ) {
    }

    public function reply(AppContact $contact, User $admin, string $body, bool $close): void
    {
        $this->contacts->reply($contact, $admin, $body);

        if ($close) {
            $this->contacts->close($contact);
        }
    }
}