<?php

namespace App\Mail\Listener;

use App\Billing\Helper\PeriodHelper;
use App\Entity\User\User;
use App\Mail\Enum\MailSubject;
use App\Mail\Enum\MailText;
use App\Mail\Service\MailService;
use App\User\Event\AccountDeletedEvent;
use App\User\Event\GuestAccountCreatedEvent;
use App\User\Event\PasswordChangedEvent;
use App\User\Event\PasswordResetRequestedEvent;
use App\User\Event\UserRegisteredEvent;
use App\User\Event\UserSuspendedEvent;
use DateTimeImmutable;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Event\Attribute\AsListener;

class UserMailListener
{
    public const PRO_CONTEXT = 'pro';

    public function __construct(
        private MailService $mail,
        #[Autowire(config: 'packages.mail.supportAddress')]
        private string $supportAddress = 'aide@kreno.fr',
    ) {
    }

    #[AsListener(UserRegisteredEvent::class)]
    public function onRegistered(UserRegisteredEvent $event): void
    {
        $subject = MailSubject::WELCOME->text($event->user->getFirstName());

        $this->mail->toUser(MailService::CLIENT, $event->user, $subject, 'welcome', [
            'verifyUrl' => $this->mail->url('app_verify_email', ['token' => $event->verificationToken]),
            'expiresIn' => MailText::SEVEN_DAYS->text(),
            'alertsUrl' => $this->mail->url('account_alerts'),
            'homeUrl' => $this->mail->url('home_index'),
        ]);
    }

    #[AsListener(GuestAccountCreatedEvent::class)]
    public function onGuestAccountCreated(GuestAccountCreatedEvent $event): void
    {
        $user = $event->getUser();
        $subject = MailSubject::WELCOME->text($user->getFirstName());

        $this->mail->toUser(MailService::CLIENT, $user, $subject, 'guest-account', [
            'passwordUrl' => $this->mail->url('app_reset_password', ['token' => $event->getPasswordToken()]),
            'expiresIn' => MailText::SEVEN_DAYS->text(),
            'forgotUrl' => $this->mail->url('app_forgot_password'),
            'bookingsUrl' => $this->mail->url('account_bookings'),
        ]);
    }

    #[AsListener(PasswordResetRequestedEvent::class)]
    public function onPasswordResetRequested(PasswordResetRequestedEvent $event): void
    {
        $token = ['token' => $event->resetToken];

        if ($event->context === self::PRO_CONTEXT) {
            $this->mail->toUser(MailService::PRO, $event->user, MailSubject::PRO_RESET_PASSWORD->text(), 'pro-reset', [
                'resetUrl' => $this->mail->url('pro_reset_password', $token),
                'expiresIn' => MailText::ONE_HOUR->text(),
            ]);

            return;
        }

        $this->mail->toUser(MailService::CLIENT, $event->user, MailSubject::RESET_PASSWORD->text(), 'reset-password', [
            'resetUrl' => $this->mail->url('app_reset_password', $token),
            'expiresIn' => MailText::ONE_HOUR->text(),
        ]);
    }

    #[AsListener(PasswordChangedEvent::class)]
    public function onPasswordChanged(PasswordChangedEvent $event): void
    {
        $now = new DateTimeImmutable();
        $context = [
            'changedAt' => MailText::DATE_AT_TIME->text($now->format('d/m/Y'), $now->format('H:i')),
            'supportEmail' => $this->supportAddress,
        ];

        if ($this->isPro($event->user)) {
            $context['forgotUrl'] = $this->mail->url('pro_forgot_password');
            $subject = MailSubject::PRO_PASSWORD_CHANGED->text();

            $this->mail->toUser(MailService::PRO, $event->user, $subject, 'pro-password-changed', $context);

            return;
        }

        $context['forgotUrl'] = $this->mail->url('app_forgot_password');
        $subject = MailSubject::PASSWORD_CHANGED->text();

        $this->mail->toUser(MailService::CLIENT, $event->user, $subject, 'password-changed', $context);
    }

    #[AsListener(AccountDeletedEvent::class)]
    public function onAccountDeleted(AccountDeletedEvent $event): void
    {
        $this->mail->send(MailService::CLIENT, $event->email, MailSubject::ACCOUNT_DELETED->text(), 'account-deleted', [
            'firstName' => $event->firstName,
            'homeUrl' => $this->mail->url('home_index'),
        ]);
    }

    #[AsListener(UserSuspendedEvent::class)]
    public function onSuspended(UserSuspendedEvent $event): void
    {
        $subject = MailSubject::ACCOUNT_SUSPENDED->text();

        $this->mail->toUser(MailService::SUPPORT, $event->user, $subject, 'account-suspended', [
            'reason' => $event->reason,
            'automatic' => $event->automatic,
            'until' => PeriodHelper::day($event->user->getSuspendedUntil()),
            'supportEmail' => $this->supportAddress,
        ]);
    }

    private function isPro(User $user): bool
    {
        return !$user->getMemberships()->isEmpty();
    }
}