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
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyInvitation;
use App\Entity\Company\CompanyMember;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentRole;
use App\Establishment\Event\EstablishmentResumedEvent;
use App\Establishment\Event\EstablishmentSuspendedEvent;
use App\Mail\Enum\MailSubject;
use App\Mail\Enum\MailText;
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
        $subject = MailSubject::PRO_VERIFY_CODE->text($event->verificationCode);

        $this->mail->toUser(MailService::PRO, $event->user, $subject, 'pro-verify-code', [
            'code' => $event->verificationCode,
            'company' => $event->company->getName(),
            'expiresIn' => MailText::THIRTY_MINUTES->text(),
            'signupUrl' => $this->mail->url('pro_signup'),
        ]);
    }

    #[AsListener(InvitationSentEvent::class)]
    public function onInvitationSent(InvitationSentEvent $event): void
    {
        $invitation = $event->invitation;
        $inviter = $invitation->getInvitedBy();
        $inviterName = $this->inviterName($inviter);
        $target = $invitation->getEstablishment()?->getName() ?? $invitation->getCompany()?->getName();
        $subject = MailSubject::PRO_INVITE->text($inviterName, $target);

        $this->mail->send(MailService::PRO, (string) $invitation->getEmail(), $subject, 'pro-invite', [
            'inviter' => $inviterName,
            'company' => $invitation->getCompany()?->getName(),
            'establishment' => $this->context->establishment($invitation->getEstablishment()),
            'target' => $target,
            'role' => RoleHelper::label($this->invitationRole($invitation)),
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

        $this->mail->toUser(MailService::PRO, $user, MailSubject::ROLE_CHANGED->text(), 'pro-role-changed', [
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

        $name = UserNameHelper::fullName($user);
        $subject = MailSubject::MEMBER_JOINED->text($name, $company->getName());

        $this->mail->send(MailService::PRO, $this->colleagues($company, $user), $subject, 'pro-member-joined', [
            'member' => $name,
            'email' => $user->getEmail(),
            'company' => $company->getName(),
            'role' => RoleHelper::label($this->memberRole($member)),
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

        $company = $event->member->getCompany()?->getName();
        $subject = MailSubject::MEMBER_REMOVED->text($company);

        $this->mail->toUser(MailService::PRO, $user, $subject, 'pro-member-removed', [
            'company' => $company,
            'supportEmail' => $this->supportAddress,
        ]);
    }

    #[AsListener(EstablishmentSubmittedEvent::class)]
    public function onSubmitted(EstablishmentSubmittedEvent $event): void
    {
        $establishment = $event->establishment;
        $subject = MailSubject::ESTABLISHMENT_SUBMITTED->text($establishment->getName());

        $this->sendToAdmins($establishment, $subject, 'pro-submitted', [
            'establishment' => $this->context->establishment($establishment),
            'delay' => MailText::TWO_BUSINESS_DAYS->text(),
            'dashboardUrl' => $this->mail->url('pro_dashboard'),
        ]);

        $subject = MailSubject::MODERATION_SUBMITTED->text($establishment->getName());

        $this->mail->send(MailService::PRO, $this->moderationAddress, $subject, 'moderation-submitted', [
            'establishment' => $this->moderationContext($establishment),
        ]);
    }

    #[AsListener(EstablishmentApprovedEvent::class)]
    public function onApproved(EstablishmentApprovedEvent $event): void
    {
        $establishment = $event->establishment;
        $recipients = $this->recipients->establishment($establishment);
        $subject = MailSubject::ESTABLISHMENT_APPROVED->text($establishment->getName());

        $this->mail->send(MailService::PRO, $recipients, $subject, 'pro-approved', [
            'establishment' => $this->context->establishment($establishment),
            'slotUrl' => $this->mail->url('pro_slot_new'),
            'dashboardUrl' => $this->mail->url('pro_dashboard'),
        ]);
    }

    #[AsListener(EstablishmentRejectedEvent::class)]
    public function onRejected(EstablishmentRejectedEvent $event): void
    {
        $establishment = $event->establishment;
        $subject = MailSubject::ESTABLISHMENT_REJECTED->text($establishment->getName());

        $this->sendToAdmins($establishment, $subject, 'pro-rejected', [
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
        $subject = MailSubject::DOCUMENT_REQUESTED->text($establishment->getName());

        $this->sendToAdmins($establishment, $subject, 'pro-document-requested', [
            'establishment' => $this->context->establishment($establishment),
            'message' => $event->message,
            'replyEmail' => $this->moderationAddress,
        ], $this->moderationAddress);
    }

    #[AsListener(EstablishmentSuspendedEvent::class)]
    public function onEstablishmentSuspended(EstablishmentSuspendedEvent $event): void
    {
        $establishment = $event->getEstablishment();
        $subject = MailSubject::ESTABLISHMENT_SUSPENDED->text($establishment->getName());

        $this->sendToEstablishment($establishment, $subject, 'pro-establishment-suspended', [
            'reason' => $event->getReason(),
            'supportEmail' => $this->supportAddress,
        ]);
    }

    #[AsListener(EstablishmentResumedEvent::class)]
    public function onEstablishmentResumed(EstablishmentResumedEvent $event): void
    {
        $establishment = $event->getEstablishment();
        $subject = MailSubject::ESTABLISHMENT_RESUMED->text($establishment->getName());

        $this->sendToEstablishment($establishment, $subject, 'pro-establishment-resumed', [
            'slotUrl' => $this->mail->url('pro_slot_new'),
        ]);
    }

    private function sendToAdmins(
        CompanyEstablishment $establishment,
        string $subject,
        string $template,
        array $context,
        ?string $replyTo = null,
    ): void {
        $recipients = $this->recipients->admins($establishment->getCompany());

        $this->mail->send(MailService::PRO, $recipients, $subject, $template, $context, $replyTo);
    }

    private function sendToEstablishment(
        CompanyEstablishment $establishment,
        string $subject,
        string $template,
        array $context,
    ): void {
        $context += [
            'establishment' => $this->context->establishment($establishment),
            'dashboardUrl' => $this->mail->url('pro_dashboard'),
        ];

        $recipients = $this->recipients->establishment($establishment);

        $this->mail->send(MailService::PRO, $recipients, $subject, $template, $context);
    }

    private function inviterName(?User $inviter): string
    {
        if ($inviter === null) {
            return MailText::DEFAULT_INVITER->text();
        }

        return UserNameHelper::fullName($inviter);
    }

    private function invitationRole(CompanyInvitation $invitation): string
    {
        return $invitation->getRole()?->value
            ?? $invitation->getEstablishmentRole()?->value
            ?? EstablishmentRole::EMPLOYEE->value;
    }

    private function memberRole(CompanyMember $member): string
    {
        return $member->getRole()?->value
            ?? $member->getEstablishmentRoles()->first()?->getRole()->value
            ?? EstablishmentRole::EMPLOYEE->value;
    }

    private function colleagues(Company $company, User $user): array
    {
        return array_values(array_diff($this->recipients->admins($company), [$this->mail->address($user)]));
    }

    private function moderationContext(CompanyEstablishment $establishment): array
    {
        return $this->context->establishment($establishment) + [
                'id' => $establishment->getId(),
                'siret' => $establishment->getSiret(),
                'company' => $establishment->getCompany()?->getName(),
            ];
    }
}