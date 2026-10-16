<?php

namespace App\Mail\Handler;

use App\Mail\Message\SendEmailMessage;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Mailer\MailerManagerInterface;
use NeoPHP\Component\Mailer\Mime\Email;
use NeoPHP\Component\View\ViewManagerInterface;
use NeoPHP\Package\Queue\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendEmailHandler
{
    public function __construct(
        private MailerManagerInterface $mailer,
        private ViewManagerInterface $views,
        #[Autowire(config: 'packages.mail.footer')]
        private array $footer = [],
    ) {
    }

    public function __invoke(SendEmailMessage $message): void
    {
        $html = $this->views->render(sprintf('emails/%s.html.twig', $message->getTemplate()), $message->getContext() + [
                'subject' => $message->getSubject(),
                'footer' => $this->footer,
            ]);

        $email = (new Email())
            ->from($message->getFrom())
            ->to(...$message->getTo())
            ->subject($message->getSubject())
            ->html($html)
            ->addHeader('X-Kreno-Template', $message->getTemplate());

        if ($message->getReplyTo() !== null) {
            $email->replyTo($message->getReplyTo());
        }

        $this->mailer->send($email);
    }
}