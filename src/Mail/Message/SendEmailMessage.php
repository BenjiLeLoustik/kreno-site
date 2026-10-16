<?php

namespace App\Mail\Message;

use NeoPHP\Package\Queue\Attribute\AsMessage;

#[AsMessage(queue: 'emails')]
class SendEmailMessage
{
    public function __construct(
        private string $template,
        private array $to,
        private string $subject,
        private string $from,
        private array $context = [],
        private ?string $replyTo = null,
    ) {
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getTo(): array
    {
        return $this->to;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getReplyTo(): ?string
    {
        return $this->replyTo;
    }
}