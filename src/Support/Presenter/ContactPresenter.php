<?php

namespace App\Support\Presenter;

use App\Entity\AppContact;
use App\Entity\AppContactMessage;
use App\Enum\Contact\AppContactAuthor;
use App\Enum\Contact\AppContactStatus;
use App\Support\Service\ContactService;
use App\User\Helper\UserNameHelper;

class ContactPresenter
{
    public function row(AppContact $contact): array
    {
        $last = $contact->getMessages()->last();

        return [
            'id' => $contact->getId(),
            'token' => $contact->getToken(),
            'topic' => ContactService::topicLabel($contact->getTopic()),
            'email' => $contact->getEmail(),
            'reference' => $contact->getReference(),
            'status' => $contact->getStatus()?->value,
            'statusLabel' => $contact->getStatus()?->label(),
            'customerStatus' => $contact->getStatus()?->customerLabel(),
            'tone' => $contact->getStatus()?->tone() ?? 'info',
            'excerpt' => $last instanceof AppContactMessage ? mb_strimwidth((string) $last->getBody(), 0, 120, '…') : null,
            'messagesCount' => $contact->getMessages()->count(),
            'createdAt' => $contact->getCreatedAt(),
            'lastMessageAt' => $contact->getLastMessageAt(),
        ];
    }

    public function detail(AppContact $contact, bool $forTeam = true): array
    {
        $user = $contact->getUser();
        $booking = $contact->getBooking();

        return $this->row($contact) + [
                'open' => $contact->getStatus() !== AppContactStatus::CLOSED,
                'closedAt' => $contact->getClosedAt(),
                'user' => $user === null ? null : ['id' => $user->getId(), 'name' => UserNameHelper::fullName($user), 'email' => $user->getEmail()],
                'booking' => $booking === null ? null : [
                    'id' => $booking->getId(),
                    'reference' => $booking->getReference(),
                    'establishment' => $booking->getSlot()?->getEstablishment()?->getName(),
                    'status' => $booking->getStatus()->label(),
                    'customerEmail' => $booking->getCustomer()?->getEmail(),
                ],
                'messages' => array_values(array_map(fn (AppContactMessage $message): array => $this->message($message, $forTeam), $contact->getMessages()->toArray())),
            ];
    }

    private function message(AppContactMessage $message, bool $forTeam): array
    {
        $team = $message->getAuthorType() === AppContactAuthor::TEAM;
        $author = $message->getAuthor();

        return [
            'team' => $team,
            'author' => $team ? ($forTeam && $author !== null ? UserNameHelper::fullName($author) . ' · équipe' : 'Équipe Kréno') : ($forTeam ? 'Client' : 'Vous'),
            'body' => $message->getBody(),
            'createdAt' => $message->getCreatedAt(),
        ];
    }
}