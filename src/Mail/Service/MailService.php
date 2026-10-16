<?php

namespace App\Mail\Service;

use App\Entity\User\User;
use App\Mail\Message\SendEmailMessage;
use App\User\Helper\UserNameHelper;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Routing\RoutingManagerInterface;
use NeoPHP\Package\Queue\Contract\MessageBusInterface;

class MailService
{
    public const CLIENT = 'client';

    public const SUPPORT = 'support';

    public const PRO = 'pro';

    public const DEFAULT_SENDER = 'Kréno <bonjour@kreno.fr>';

    public function __construct(
        private MessageBusInterface $bus,
        private RoutingManagerInterface $routing,
        #[Autowire(config: 'packages.mail.senders')]
        private array $senders = [],
    ) {
    }

    public function send(
        string $sender,
        array|string $to,
        string $subject,
        string $template,
        array $context = [],
        ?string $replyTo = null,
    ): void {
        $recipients = $this->recipients($to);

        if ($recipients === []) {
            return;
        }

        $message = new SendEmailMessage($template, $recipients, $subject, $this->from($sender), $context, $replyTo);

        $this->bus->dispatch($message);
    }

    public function toUser(
        string $sender,
        User $user,
        string $subject,
        string $template,
        array $context = [],
        bool $optional = false,
    ): void {
        if ($user->getEmail() === null) {
            return;
        }

        if ($optional && !$user->isEmailNotifications()) {
            return;
        }

        $context += ['firstName' => $user->getFirstName()];

        $this->send($sender, $this->address($user), $subject, $template, $context);
    }

    public function address(User $user): string
    {
        $name = UserNameHelper::fullName($user);

        if ($name === '') {
            return (string) $user->getEmail();
        }

        return sprintf('%s <%s>', str_replace(['<', '>', '"'], '', $name), $user->getEmail());
    }

    public function url(string $route, array $parameters = []): string
    {
        return $this->routing->generate($route, $parameters, true);
    }

    private function recipients(array|string $to): array
    {
        $recipients = [];

        foreach ((array) $to as $address) {
            if ($address !== null && $address !== '') {
                $recipients[] = $address;
            }
        }

        return array_values(array_unique($recipients));
    }

    private function from(string $sender): string
    {
        return $this->senders[$sender] ?? $this->senders[self::CLIENT] ?? self::DEFAULT_SENDER;
    }
}