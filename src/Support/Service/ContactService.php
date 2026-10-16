<?php

namespace App\Support\Service;

use App\Entity\AppContact;
use App\Entity\AppContactMessage;
use App\Entity\Booking;
use App\Entity\User\User;
use App\Enum\Contact\AppContactAuthor;
use App\Enum\Contact\AppContactStatus;
use App\Mail\Enum\MailSubject;
use App\Mail\Enum\MailText;
use App\Mail\Service\MailService;
use App\Repository\BookingRepository;
use App\Support\Dto\ContactInput;
use App\Support\Enum\ContactTopic;
use App\Support\Exception\ContactClosedException;
use App\Support\Exception\ContactClosedForTeamException;
use App\User\Helper\TokenHelper;
use App\User\Helper\UserNameHelper;
use DateTimeImmutable;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Package\Orm\OrmManagerInterface;

class ContactService
{
    public function __construct(
        private MailService $mail,
        private BookingRepository $bookings,
        private OrmManagerInterface $orm,
        #[Autowire(config: 'packages.mail.supportAddress')]
        private string $supportAddress = 'aide@kreno.fr',
    ) {
    }

    public function send(ContactInput $input, ?User $user = null): AppContact
    {
        $reference = strtoupper(trim((string) $input->reference));

        $contact = (new AppContact())
            ->setTopic(ContactTopic::of($input->topic)->value)
            ->setEmail(mb_strtolower(trim((string) $input->email)))
            ->setUser($user)
            ->setBooking($this->booking($reference))
            ->setReference($this->nullable($reference))
            ->setStatus(AppContactStatus::NEW)
            ->setToken(TokenHelper::generate())
            ->setLastMessageAt(new DateTimeImmutable());

        $message = (new AppContactMessage())
            ->setAuthorType(AppContactAuthor::CUSTOMER)
            ->setBody(trim((string) $input->message));

        $contact->addMessage($message);
        $this->orm->persist($contact);
        $this->orm->flush();

        $this->notifyTeam($contact, $message, false);

        return $contact;
    }

    public function reply(AppContact $contact, User $admin, string $body): AppContactMessage
    {
        if ($contact->getStatus() === AppContactStatus::CLOSED) {
            throw new ContactClosedForTeamException();
        }

        $message = (new AppContactMessage())
            ->setAuthorType(AppContactAuthor::TEAM)
            ->setAuthor($admin)
            ->setBody(trim($body));

        $contact->addMessage($message);
        $contact->setStatus(AppContactStatus::ANSWERED)->setLastMessageAt(new DateTimeImmutable());
        $this->orm->flush();

        $this->notifyCustomer($contact, $message);

        return $message;
    }

    public function customerReply(AppContact $contact, string $body): AppContactMessage
    {
        if ($contact->getStatus() === AppContactStatus::CLOSED) {
            throw new ContactClosedException();
        }

        $message = (new AppContactMessage())
            ->setAuthorType(AppContactAuthor::CUSTOMER)
            ->setBody(trim($body));

        $contact->addMessage($message);
        $contact->setStatus(AppContactStatus::NEW)->setLastMessageAt(new DateTimeImmutable());
        $this->orm->flush();

        $this->notifyTeam($contact, $message, true);

        return $message;
    }

    public function close(AppContact $contact): void
    {
        $contact->setStatus(AppContactStatus::CLOSED)->setClosedAt(new DateTimeImmutable());
        $this->orm->flush();
    }

    public function reopen(AppContact $contact): void
    {
        $contact->setStatus(AppContactStatus::NEW)->setClosedAt(null);
        $this->orm->flush();
    }

    private function booking(string $reference): ?Booking
    {
        if ($reference === '') {
            return null;
        }

        return $this->bookings->findOneByReference($reference);
    }

    private function nullable(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        return $value;
    }

    private function notifyCustomer(AppContact $contact, AppContactMessage $message): void
    {
        $topic = ContactTopic::of($contact->getTopic())->label();
        $subject = MailSubject::CONTACT_REPLY->text($topic);

        $this->mail->send(MailService::SUPPORT, (string) $contact->getEmail(), $subject, 'contact-reply', [
            'firstName' => $contact->getUser()?->getFirstName(),
            'topic' => $topic,
            'reply' => $message->getBody(),
            'original' => $this->originalMessage($contact, $message),
            'reference' => $contact->getReference(),
            'url' => $this->mail->url('page_contact_show', ['token' => $contact->getToken()]),
        ], $this->supportAddress);
    }

    private function originalMessage(AppContact $contact, AppContactMessage $reply): ?string
    {
        $first = $contact->getMessages()->first();

        if (!$first instanceof AppContactMessage || $first === $reply) {
            return null;
        }

        return $first->getBody();
    }

    private function notifyTeam(AppContact $contact, AppContactMessage $message, bool $followUp): void
    {
        $topic = ContactTopic::of($contact->getTopic())->label();
        $subject = $this->teamSubject($followUp)->text($topic, $contact->getEmail());
        $now = new DateTimeImmutable();

        $this->mail->send(MailService::SUPPORT, $this->supportAddress, $subject, 'support-contact', [
            'followUp' => $followUp,
            'topic' => $topic,
            'email' => $contact->getEmail(),
            'message' => $message->getBody(),
            'reference' => $contact->getReference(),
            'booking' => $this->bookingContext($contact->getBooking()),
            'user' => $this->userContext($contact->getUser()),
            'sentAt' => MailText::DATE_AT_TIME->text($now->format('d/m/Y'), $now->format('H:i')),
            'adminUrl' => $this->mail->url('admin_contact_show', ['id' => $contact->getId()]),
        ], $contact->getEmail());
    }

    private function teamSubject(bool $followUp): MailSubject
    {
        if ($followUp) {
            return MailSubject::SUPPORT_FOLLOW_UP;
        }

        return MailSubject::SUPPORT_CONTACT;
    }

    private function bookingContext(?Booking $booking): ?array
    {
        if ($booking === null) {
            return null;
        }

        return [
            'reference' => $booking->getReference(),
            'establishment' => $booking->getSlot()?->getEstablishment()?->getName(),
            'customerEmail' => $booking->getCustomer()?->getEmail(),
            'status' => $booking->getStatus()->label(),
        ];
    }

    private function userContext(?User $user): ?array
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
}