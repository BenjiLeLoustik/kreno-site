<?php

namespace App\Mail\Listener;

use App\Mail\Enum\MailSubject;
use App\Mail\Enum\MailText;
use App\Mail\Service\MailService;
use App\User\Event\StaffInvitedEvent;
use App\User\Helper\UserNameHelper;
use NeoPHP\Component\Event\Attribute\AsListener;

class StaffMailListener
{
    public function __construct(
        private MailService $mail,
    ) {
    }

    #[AsListener(StaffInvitedEvent::class)]
    public function onInvited(StaffInvitedEvent $event): void
    {
        $inviter = UserNameHelper::fullName($event->getInvitedBy());
        $subject = MailSubject::STAFF_INVITE->text($inviter);

        $this->mail->toUser(MailService::SUPPORT, $event->getUser(), $subject, 'staff-invite', [
            'inviter' => $inviter,
            'role' => $event->getRole()->label(),
            'passwordUrl' => $this->passwordUrl($event->getPasswordToken()),
            'expiresIn' => MailText::SEVEN_DAYS->text(),
            'forgotUrl' => $this->mail->url('app_forgot_password'),
            'adminUrl' => $this->mail->url('admin_dashboard'),
        ]);
    }

    private function passwordUrl(?string $token): ?string
    {
        if ($token === null) {
            return null;
        }

        return $this->mail->url('app_reset_password', ['token' => $token]);
    }
}