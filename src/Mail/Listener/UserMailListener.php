<?php

namespace App\Mail\Listener;

use App\Billing\Helper\PeriodHelper;
use App\Entity\User\User;
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
    public function __construct(
        private MailService $mail,
        #[Autowire(config: 'packages.mail.supportAddress')]
        private string $supportAddress = 'aide@kreno.fr',
    ) {
    }

    #[AsListener(UserRegisteredEvent::class)]
    public function onRegistered(UserRegisteredEvent $event): void
    {
        $this->mail->toUser(MailService::CLIENT, $event->user, sprintf('Bienvenue sur Kréno, %s', $event->user->getFirstName()), 'welcome', [
            'verifyUrl' => $this->mail->url('app_verify_email', ['token' => $event->verificationToken]),
            'expiresIn' => '7 jours',
            'alertsUrl' => $this->mail->url('account_alerts'),
            'homeUrl' => $this->mail->url('home_index'),
        ]);
    }

    #[AsListener(GuestAccountCreatedEvent::class)]
    public function onGuestAccountCreated(GuestAccountCreatedEvent $event): void
    {
        $user = $event->getUser();

        $this->mail->toUser(MailService::CLIENT, $user, sprintf('Bienvenue sur Kréno, %s', $user->getFirstName()), 'guest-account', [
            'passwordUrl' => $this->mail->url('app_reset_password', ['token' => $event->getPasswordToken()]),
            'expiresIn' => '7 jours',
            'forgotUrl' => $this->mail->url('app_forgot_password'),
            'bookingsUrl' => $this->mail->url('account_bookings'),
        ]);
    }

    #[AsListener(PasswordResetRequestedEvent::class)]
    public function onPasswordResetRequested(PasswordResetRequestedEvent $event): void
    {
        if ($event->context === 'pro') {
            $this->mail->toUser(MailService::PRO, $event->user, 'Réinitialisation de votre mot de passe Kréno Pro', 'pro-reset', [
                'resetUrl' => $this->mail->url('pro_reset_password', ['token' => $event->resetToken]),
                'expiresIn' => '1 heure',
            ]);

            return;
        }

        $this->mail->toUser(MailService::CLIENT, $event->user, 'Réinitialise ton mot de passe Kréno', 'reset-password', [
            'resetUrl' => $this->mail->url('app_reset_password', ['token' => $event->resetToken]),
            'expiresIn' => '1 heure',
        ]);
    }

    #[AsListener(PasswordChangedEvent::class)]
    public function onPasswordChanged(PasswordChangedEvent $event): void
    {
        $context = [
            'changedAt' => (new DateTimeImmutable())->format('d/m/Y à H:i'),
            'supportEmail' => $this->supportAddress,
        ];

        if ($this->isPro($event->user)) {
            $this->mail->toUser(MailService::PRO, $event->user, 'Votre mot de passe Kréno Pro a été modifié', 'pro-password-changed', $context + [
                    'forgotUrl' => $this->mail->url('pro_forgot_password'),
                ]);

            return;
        }

        $this->mail->toUser(MailService::CLIENT, $event->user, 'Ton mot de passe Kréno a été modifié', 'password-changed', $context + [
                'forgotUrl' => $this->mail->url('app_forgot_password'),
            ]);
    }

    #[AsListener(AccountDeletedEvent::class)]
    public function onAccountDeleted(AccountDeletedEvent $event): void
    {
        $this->mail->send(MailService::CLIENT, $event->email, 'Ton compte Kréno est supprimé', 'account-deleted', [
            'firstName' => $event->firstName,
            'homeUrl' => $this->mail->url('home_index'),
        ]);
    }

    #[AsListener(UserSuspendedEvent::class)]
    public function onSuspended(UserSuspendedEvent $event): void
    {
        $this->mail->toUser(MailService::SUPPORT, $event->user, 'Ton compte Kréno est suspendu', 'account-suspended', [
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