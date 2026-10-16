<?php

namespace App\Support\Service;

use App\Entity\User\User;
use App\Mail\Service\MailService;
use App\Repository\BookingRepository;
use App\Support\Dto\ContactInput;
use App\User\Helper\UserNameHelper;
use DateTimeImmutable;
use NeoPHP\Component\Container\Attribute\Autowire;

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
        #[Autowire(config: 'packages.mail.supportAddress')]
        private string $supportAddress = 'aide@kreno.fr',
    ) {
    }

    public function send(ContactInput $input, ?User $user = null): void
    {
        $email = mb_strtolower(trim((string) $input->email));
        $reference = trim((string) $input->reference);
        $booking = $reference !== '' ? $this->bookings->findOneByReference($reference) : null;
        $topic = self::TOPICS[(string) $input->topic] ?? self::TOPICS['other'];

        $this->mail->send(MailService::SUPPORT, $this->supportAddress, sprintf('Contact · %s · %s', $topic, $email), 'support-contact', [
            'topic' => $topic,
            'email' => $email,
            'message' => trim((string) $input->message),
            'reference' => $reference !== '' ? $reference : null,
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
        ], $email);
    }
}