<?php

namespace App\Support\Presenter;

use App\Entity\AppContact;
use App\Entity\AppContactMessage;
use App\Entity\Booking;
use App\Entity\User\User;
use App\Enum\Contact\AppContactAuthor;
use App\Enum\Contact\AppContactStatus;
use App\Support\Enum\ContactAuthorLabel;
use App\Support\Enum\ContactTopic;
use App\User\Helper\UserNameHelper;

class ContactPresenter
{
    public const EXCERPT_LENGTH = 120;

    public function row(AppContact $contact): array
    {
        return [
            'id' => $contact->getId(),
            'token' => $contact->getToken(),
            'topic' => ContactTopic::of($contact->getTopic())->label(),
            'email' => $contact->getEmail(),
            'reference' => $contact->getReference(),
            'status' => $contact->getStatus()?->value,
            'statusLabel' => $contact->getStatus()?->label(),
            'customerStatus' => $contact->getStatus()?->customerLabel(),
            'tone' => $contact->getStatus()?->tone() ?? 'info',
            'excerpt' => $this->excerpt($contact),
            'messagesCount' => $contact->getMessages()->count(),
            'createdAt' => $contact->getCreatedAt(),
            'lastMessageAt' => $contact->getLastMessageAt(),
        ];
    }

    public function detail(AppContact $contact, bool $forTeam = true): array
    {
        return $this->row($contact) + [
                'open' => $contact->getStatus() !== AppContactStatus::CLOSED,
                'closedAt' => $contact->getClosedAt(),
                'user' => $this->user($contact->getUser()),
                'booking' => $this->booking($contact->getBooking()),
                'messages' => $this->messages($contact, $forTeam),
            ];
    }

    private function excerpt(AppContact $contact): ?string
    {
        $last = $contact->getMessages()->last();

        if (!$last instanceof AppContactMessage) {
            return null;
        }

        return mb_strimwidth((string) $last->getBody(), 0, self::EXCERPT_LENGTH, '…');
    }

    private function user(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->getId(),
            'name' => UserNameHelper::fullName($user),
            'email' => $user->getEmail(),
        ];
    }

    private function booking(?Booking $booking): ?array
    {
        if ($booking === null) {
            return null;
        }

        return [
            'id' => $booking->getId(),
            'reference' => $booking->getReference(),
            'establishment' => $booking->getSlot()?->getEstablishment()?->getName(),
            'status' => $booking->getStatus()->label(),
            'customerEmail' => $booking->getCustomer()?->getEmail(),
        ];
    }

    private function messages(AppContact $contact, bool $forTeam): array
    {
        $messages = [];

        foreach ($contact->getMessages() as $message) {
            $messages[] = $this->message($message, $forTeam);
        }

        return $messages;
    }

    private function message(AppContactMessage $message, bool $forTeam): array
    {
        $team = $message->getAuthorType() === AppContactAuthor::TEAM;

        return [
            'team' => $team,
            'author' => $this->author($message, $team, $forTeam),
            'body' => $message->getBody(),
            'createdAt' => $message->getCreatedAt(),
        ];
    }

    private function author(AppContactMessage $message, bool $team, bool $forTeam): string
    {
        if (!$team) {
            return $this->customerAuthor($forTeam);
        }

        $author = $message->getAuthor();

        if ($forTeam && $author !== null) {
            return ContactAuthorLabel::TEAM_MEMBER->text(UserNameHelper::fullName($author));
        }

        return ContactAuthorLabel::TEAM->text();
    }

    private function customerAuthor(bool $forTeam): string
    {
        if ($forTeam) {
            return ContactAuthorLabel::CUSTOMER->text();
        }

        return ContactAuthorLabel::YOU->text();
    }
}