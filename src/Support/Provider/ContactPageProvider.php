<?php

namespace App\Support\Provider;

use App\Entity\AppContact;
use App\Entity\User\User;
use App\Repository\AppContactRepository;
use App\Support\Exception\ContactNotFoundException;
use App\Support\Presenter\ContactPresenter;

class ContactPageProvider
{
    public const TOKEN_PATTERN = '[A-Za-z0-9_\-]{20,64}';

    public function __construct(
        private AppContactRepository $contacts,
        private ContactPresenter $presenter,
    ) {
    }

    public function byToken(string $token): AppContact
    {
        $contact = $this->contacts->findOneByToken($token);

        if ($contact === null) {
            throw new ContactNotFoundException();
        }

        return $contact;
    }

    public function show(string $token): array
    {
        return [
            'contact' => $this->presenter->detail($this->byToken($token), false),
        ];
    }

    public function messages(User $user): array
    {
        $contacts = [];

        foreach ($this->contacts->findForUser($user) as $contact) {
            $contacts[] = $this->presenter->row($contact);
        }

        return ['contacts' => $contacts];
    }
}