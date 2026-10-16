<?php

namespace App\Mail\Listener;

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

        $this->mail->toUser(MailService::SUPPORT, $event->getUser(), sprintf('%s vous invite dans l’équipe Kréno', $inviter), 'staff-invite', [
            'inviter' => $inviter,
            'role' => $event->getRole()->label(),
            'passwordUrl' => $event->getPasswordToken() !== null ? $this->mail->url('app_reset_password', ['token' => $event->getPasswordToken()]) : null,
            'expiresIn' => '7 jours',
            'forgotUrl' => $this->mail->url('app_forgot_password'),
            'adminUrl' => $this->mail->url('admin_dashboard'),
        ]);
    }
}