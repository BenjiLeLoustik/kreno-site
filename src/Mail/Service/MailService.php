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

    public function __construct(
        private MessageBusInterface $bus,
        private RoutingManagerInterface $routing,
        #[Autowire(config: 'packages.mail.senders')]
        private array $senders = [],
    ) {
    }

    public function send(string $sender, array|string $to, string $subject, string $template, array $context = [], ?string $replyTo = null): void
    {
        $recipients = array_values(array_unique(array_filter(is_array($to) ? $to : [$to], static fn (?string $address): bool => $address !== null && $address !== '')));

        if ($recipients === []) {
            return;
        }

        $this->bus->dispatch(new SendEmailMessage($template, $recipients, $subject, $this->senders[$sender] ?? $this->senders[self::CLIENT] ?? 'Kréno <bonjour@kreno.fr>', $context, $replyTo));
    }

    public function toUser(string $sender, User $user, string $subject, string $template, array $context = [], bool $optional = false): void
    {
        if ($user->getEmail() === null || ($optional && !$user->isEmailNotifications())) {
            return;
        }

        $this->send($sender, $this->address($user), $subject, $template, $context + ['firstName' => $user->getFirstName()]);
    }

    public function address(User $user): string
    {
        $name = UserNameHelper::fullName($user);

        return $name !== '' ? sprintf('%s <%s>', str_replace(['<', '>', '"'], '', $name), $user->getEmail()) : (string) $user->getEmail();
    }

    public function url(string $route, array $parameters = []): string
    {
        return $this->routing->generate($route, $parameters, true);
    }
}