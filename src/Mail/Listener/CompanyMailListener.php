<?php

namespace App\Mail\Listener;

use App\Billing\Helper\PeriodHelper;
use App\Company\Event\DocumentRequestedEvent;
use App\Company\Event\EstablishmentApprovedEvent;
use App\Company\Event\EstablishmentRejectedEvent;
use App\Company\Event\EstablishmentSubmittedEvent;
use App\Company\Event\InvitationSentEvent;
use App\Company\Event\MemberJoinedEvent;
use App\Company\Event\MemberRemovedEvent;
use App\Company\Event\MemberRoleChangedEvent;
use App\Company\Event\ProAccountCreatedEvent;
use App\Company\Helper\RoleHelper;
use App\Entity\Company\CompanyEstablishment;
use App\Establishment\Event\EstablishmentResumedEvent;
use App\Establishment\Event\EstablishmentSuspendedEvent;
use App\Mail\Presenter\MailContextPresenter;
use App\Mail\Provider\RecipientProvider;
use App\Mail\Service\MailService;
use App\User\Helper\UserNameHelper;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Event\Attribute\AsListener;

class CompanyMailListener
{
    public function __construct(
        private MailService $mail,
        private RecipientProvider $recipients,
        private MailContextPresenter $context,
        #[Autowire(config: 'packages.mail.moderationAddress')]
        private string $moderationAddress = 'moderation@kreno.fr',
        #[Autowire(config: 'packages.mail.supportAddress')]
        private string $supportAddress = 'aide@kreno.fr',
    ) {
    }

    #[AsListener(ProAccountCreatedEvent::class)]
    public function onProAccountCreated(ProAccountCreatedEvent $event): void
    {
        $this->mail->toUser(MailService::PRO, $event->user, sprintf('Votre code de vérification Kréno Pro : %s', $event->verificationCode), 'pro-verify-code', [
            'code' => $event->verificationCode,
            'company' => $event->company->getName(),
            'expiresIn' => '30 minutes',
            'signupUrl' => $this->mail->url('pro_signup'),
        ]);
    }

    #[AsListener(InvitationSentEvent::class)]
    public function onInvitationSent(InvitationSentEvent $event): void
    {
        $invitation = $event->invitation;
        $inviter = $invitation->getInvitedBy();
        $inviterName = $inviter !== null ? UserNameHelper::fullName($inviter) : 'L’équipe Kréno';
        $target = $invitation->getEstablishment()?->getName() ?? $invitation->getCompany()?->getName();
        $role = $invitation->getRole()?->value ?? $invitation->getEstablishmentRole()?->value ?? 'employee';

        $this->mail->send(MailService::PRO, (string) $invitation->getEmail(), sprintf('%s vous invite à rejoindre %s sur Kréno Pro', $inviterName, $target), 'pro-invite', [
            'inviter' => $inviterName,
            'company' => $invitation->getCompany()?->getName(),
            'establishment' => $this->context->establishment($invitation->getEstablishment()),
            'target' => $target,
            'role' => RoleHelper::label($role),
            'acceptUrl' => $this->mail->url('pro_invitation_show', ['token' => $event->token]),
            'expiresAt' => PeriodHelper::day($invitation->getExpiresAt()),
        ], $inviter?->getEmail());
    }

    #[AsListener(MemberRoleChangedEvent::class)]
    public function onRoleChanged(MemberRoleChangedEvent $event): void
    {
        $user = $event->member->getUser();

        if ($user === null) {
            return;
        }

        $this->mail->toUser(MailService::PRO, $user, 'Votre rôle a changé sur Kréno Pro', 'pro-role-changed', [
            'company' => $event->member->getCompany()?->getName(),
            'establishment' => $event->establishment?->getName(),
            'previousRole' => RoleHelper::label($event->previousRole),
            'newRole' => RoleHelper::label($event->newRole),
            'dashboardUrl' => $this->mail->url('pro_dashboard'),
        ]);
    }

    #[AsListener(MemberJoinedEvent::class)]
    public function onMemberJoined(MemberJoinedEvent $event): void
    {
        $member = $event->member;
        $user = $member->getUser();
        $company = $member->getCompany();

        if ($user === null || $company === null) {
            return;
        }

        $recipients = array_values(array_diff($this->recipients->admins($company), [$this->mail->address($user)]));

        $this->mail->send(MailService::PRO, $recipients, sprintf('%s a rejoint %s sur Kréno Pro', UserNameHelper::fullName($user), $company->getName()), 'pro-member-joined', [
            'member' => UserNameHelper::fullName($user),
            'email' => $user->getEmail(),
            'company' => $company->getName(),
            'role' => RoleHelper::label($member->getRole()?->value ?? $member->getEstablishmentRoles()->first()?->getRole()->value ?? 'employee'),
            'dashboardUrl' => $this->mail->url('pro_dashboard'),
        ]);
    }

    #[AsListener(MemberRemovedEvent::class)]
    public function onMemberRemoved(MemberRemovedEvent $event): void
    {
        $user = $event->member->getUser();

        if ($user === null) {
            return;
        }

        $this->mail->toUser(MailService::PRO, $user, sprintf('Votre accès à %s sur Kréno Pro a été retiré', $event->member->getCompany()?->getName()), 'pro-member-removed', [
            'company' => $event->member->getCompany()?->getName(),
            'supportEmail' => $this->supportAddress,
        ]);
    }

    #[AsListener(EstablishmentSubmittedEvent::class)]
    public function onSubmitted(EstablishmentSubmittedEvent $event): void
    {
        $establishment = $event->establishment;

        $this->mail->send(MailService::PRO, $this->recipients->admins($establishment->getCompany()), sprintf('%s : inscription reçue, vérification en cours', $establishment->getName()), 'pro-submitted', [
            'establishment' => $this->context->establishment($establishment),
            'delay' => '48 h ouvrées',
            'dashboardUrl' => $this->mail->url('pro_dashboard'),
        ]);

        $this->mail->send(MailService::PRO, $this->moderationAddress, sprintf('Nouvel établissement à valider : %s', $establishment->getName()), 'moderation-submitted', [
            'establishment' => $this->context->establishment($establishment) + [
                    'id' => $establishment->getId(),
                    'siret' => $establishment->getSiret(),
                    'company' => $establishment->getCompany()?->getName(),
                ],
        ]);
    }

    #[AsListener(EstablishmentApprovedEvent::class)]
    public function onApproved(EstablishmentApprovedEvent $event): void
    {
        $establishment = $event->establishment;

        $this->mail->send(MailService::PRO, $this->recipients->establishment($establishment), sprintf('%s est en ligne sur Kréno', $establishment->getName()), 'pro-approved', [
            'establishment' => $this->context->establishment($establishment),
            'slotUrl' => $this->mail->url('pro_slot_new'),
            'dashboardUrl' => $this->mail->url('pro_dashboard'),
        ]);
    }

    #[AsListener(EstablishmentRejectedEvent::class)]
    public function onRejected(EstablishmentRejectedEvent $event): void
    {
        $establishment = $event->establishment;

        $this->mail->send(MailService::PRO, $this->recipients->admins($establishment->getCompany()), sprintf('%s : votre inscription n’a pas pu être validée', $establishment->getName()), 'pro-rejected', [
            'establishment' => $this->context->establishment($establishment),
            'reason' => $event->reason,
            'message' => $event->message,
            'signupUrl' => $this->mail->url('pro_signup'),
            'supportEmail' => $this->supportAddress,
        ], $this->supportAddress);
    }

    #[AsListener(DocumentRequestedEvent::class)]
    public function onDocumentRequested(DocumentRequestedEvent $event): void
    {
        $establishment = $event->establishment;

        $this->mail->send(MailService::PRO, $this->recipients->admins($establishment->getCompany()), sprintf('%s : un justificatif est nécessaire', $establishment->getName()), 'pro-document-requested', [
            'establishment' => $this->context->establishment($establishment),
            'message' => $event->message,
            'replyEmail' => $this->moderationAddress,
        ], $this->moderationAddress);
    }

    #[AsListener(EstablishmentSuspendedEvent::class)]
    public function onEstablishmentSuspended(EstablishmentSuspendedEvent $event): void
    {
        $establishment = $event->getEstablishment();

        $this->sendToEstablishment($establishment, sprintf('%s est suspendu sur Kréno', $establishment->getName()), 'pro-establishment-suspended', [
            'reason' => $event->getReason(),
            'supportEmail' => $this->supportAddress,
        ]);
    }

    #[AsListener(EstablishmentResumedEvent::class)]
    public function onEstablishmentResumed(EstablishmentResumedEvent $event): void
    {
        $establishment = $event->getEstablishment();

        $this->sendToEstablishment($establishment, sprintf('%s est de nouveau en ligne sur Kréno', $establishment->getName()), 'pro-establishment-resumed', [
            'slotUrl' => $this->mail->url('pro_slot_new'),
        ]);
    }

    private function sendToEstablishment(CompanyEstablishment $establishment, string $subject, string $template, array $context): void
    {
        $this->mail->send(MailService::PRO, $this->recipients->establishment($establishment), $subject, $template, $context + [
                'establishment' => $this->context->establishment($establishment),
                'dashboardUrl' => $this->mail->url('pro_dashboard'),
            ]);
    }
}