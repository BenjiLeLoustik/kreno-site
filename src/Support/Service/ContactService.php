<?php

namespace App\Support\Service;

use App\Entity\AppContact;
use App\Entity\AppContactMessage;
use App\Entity\User\User;
use App\Enum\Contact\AppContactAuthor;
use App\Enum\Contact\AppContactStatus;
use App\Mail\Service\MailService;
use App\Repository\BookingRepository;
use App\Shared\Exception\BusinessRuleException;
use App\Support\Dto\ContactInput;
use App\User\Helper\TokenHelper;
use App\User\Helper\UserNameHelper;
use DateTimeImmutable;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Package\Orm\OrmManagerInterface;

class ContactService
{
    public const TOPICS = [
        'booking' => 'Une réservation',
        'account' => 'Mon compte',
        'report' => 'Signaler un établissement',
        'other' => 'Autre',
    ];

    public function __construct(
        private MailService $mail,
        private BookingRepository $bookings,
        private OrmManagerInterface $orm,
        #[Autowire(config: 'packages.mail.supportAddress')]
        private string $supportAddress = 'aide@kreno.fr',
    ) {
    }

    public static function topicLabel(?string $topic): string
    {
        return self::TOPICS[(string) $topic] ?? self::TOPICS['other'];
    }

    public function send(ContactInput $input, ?User $user = null): AppContact
    {
        $now = new DateTimeImmutable();
        $reference = strtoupper(trim((string) $input->reference));
        $booking = $reference !== '' ? $this->bookings->findOneByReference($reference) : null;

        $contact = (new AppContact())
            ->setTopic(isset(self::TOPICS[(string) $input->topic]) ? (string) $input->topic : 'other')
            ->setEmail(mb_strtolower(trim((string) $input->email)))
            ->setUser($user)
            ->setBooking($booking)
            ->setReference($reference !== '' ? $reference : null)
            ->setStatus(AppContactStatus::NEW)
            ->setToken(TokenHelper::generate())
            ->setLastMessageAt($now);

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
            throw new BusinessRuleException('Cette demande est fermée : rouvrez-la pour répondre.');
        }

        $message = (new AppContactMessage())
            ->setAuthorType(AppContactAuthor::TEAM)
            ->setAuthor($admin)
            ->setBody(trim($body));

        $contact->addMessage($message);
        $contact->setStatus(AppContactStatus::ANSWERED)->setLastMessageAt(new DateTimeImmutable());
        $this->orm->flush();
        $first = $contact->getMessages()->first();

        $this->mail->send(MailService::SUPPORT, (string) $contact->getEmail(), sprintf('Réponse à votre demande · %s', self::topicLabel($contact->getTopic())), 'contact-reply', [
            'firstName' => $contact->getUser()?->getFirstName(),
            'topic' => self::topicLabel($contact->getTopic()),
            'reply' => $message->getBody(),
            'original' => $first instanceof AppContactMessage && $first !== $message ? $first->getBody() : null,
            'reference' => $contact->getReference(),
            'url' => $this->mail->url('page_contact_show', ['token' => $contact->getToken()]),
        ], $this->supportAddress);

        return $message;
    }

    public function customerReply(AppContact $contact, string $body): AppContactMessage
    {
        if ($contact->getStatus() === AppContactStatus::CLOSED) {
            throw new BusinessRuleException('Cette demande est fermée. Écrivez-nous depuis la page d\'aide pour en ouvrir une nouvelle.');
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

    private function notifyTeam(AppContact $contact, AppContactMessage $message, bool $followUp): void
    {
        $booking = $contact->getBooking();
        $user = $contact->getUser();
        $topic = self::topicLabel($contact->getTopic());

        $this->mail->send(MailService::SUPPORT, $this->supportAddress, sprintf('%s · %s · %s', $followUp ? 'Réponse client' : 'Contact', $topic, $contact->getEmail()), 'support-contact', [
            'followUp' => $followUp,
            'topic' => $topic,
            'email' => $contact->getEmail(),
            'message' => $message->getBody(),
            'reference' => $contact->getReference(),
            'booking' => $booking === null ? null : [
                'reference' => $booking->getReference(),
                'establishment' => $booking->getSlot()?->getEstablishment()?->getName(),
                'customerEmail' => $booking->getCustomer()?->getEmail(),
                'status' => $booking->getStatus()->label(),
            ],
            'user' => $user === null ? null : [
                'id' => $user->getId(),
                'name' => UserNameHelper::fullName($user),
                'email' => $user->getEmail(),
            ],
            'sentAt' => (new DateTimeImmutable())->format('d/m/Y à H:i'),
            'adminUrl' => $this->mail->url('admin_contact_show', ['id' => $contact->getId()]),
        ], $contact->getEmail());
    }
}