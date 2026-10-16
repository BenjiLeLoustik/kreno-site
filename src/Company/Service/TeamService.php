<?php

namespace App\Company\Service;

use App\Company\Enum\CompanyRole;
use App\Company\Enum\MemberStatus;
use App\Company\Dto\InvitationInput;
use App\Company\Dto\MemberRoleInput;
use App\Company\Event\InvitationSentEvent;
use App\Company\Event\MemberJoinedEvent;
use App\Company\Event\MemberRemovedEvent;
use App\Company\Event\MemberRoleChangedEvent;
use App\Company\Helper\RoleHelper;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyInvitation;
use App\Entity\Company\CompanyMember;
use App\Entity\Establishment\EstablishmentMember;
use App\Entity\User\User;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Repository\Company\CompanyInvitationRepository;
use App\Repository\Company\CompanyMemberRepository;
use App\Shared\Exception\BusinessRuleException;
use App\User\Exception\InvalidTokenException;
use App\User\Helper\TokenHelper;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class TeamService
{
    private const INVITATION_LIFETIME = '+7 days';

    public function __construct(
        private OrmManagerInterface $orm,
        private CompanyMemberRepository $members,
        private CompanyInvitationRepository $invitations,
        private CompanyEstablishmentRepository $establishments,
        private PermissionResolver $resolver,
        private EventManagerInterface $events,
    ) {
    }

    public function invite(CompanyMember $actor, InvitationInput $input): CompanyInvitation
    {
        $company = $actor->getCompany() ?? throw new BusinessRuleException('Entreprise introuvable.');
        $email = mb_strtolower(trim($input->email));
        $establishment = $this->resolveEstablishment($company, $input->role, $input->establishmentId);

        $this->assertCanGrant($actor, $input->role, $establishment);

        foreach ($company->getMembers() as $member) {
            if ($member->getUser()?->getEmail() === $email) {
                throw new BusinessRuleException('{email} fait déjà partie de l\'équipe.', ['email' => $email]);
            }
        }

        $now = new DateTimeImmutable();
        $this->invitations->findPendingForEmail($company, $email, $now)?->setExpiresAt($now);

        $token = TokenHelper::generate();
        $invitation = (new CompanyInvitation())
            ->setEmail($email)
            ->setRole(RoleHelper::isCompanyRole($input->role) ? CompanyRole::from($input->role) : null)
            ->setEstablishment($establishment)
            ->setEstablishmentRole(RoleHelper::establishmentRole($input->role))
            ->setInvitedBy($actor->getUser())
            ->setTokenHash(TokenHelper::hash($token))
            ->setExpiresAt(new DateTimeImmutable(self::INVITATION_LIFETIME));

        $company->addInvitation($invitation);
        $this->orm->persist($invitation);
        $this->orm->flush();

        $this->events->dispatch(new InvitationSentEvent($invitation, $token));

        return $invitation;
    }

    public function revoke(CompanyMember $actor, CompanyInvitation $invitation): void
    {
        $this->assertCanGrant($actor, $invitation->getRole()?->value ?? $invitation->getEstablishmentRole()?->value ?? 'employee', $invitation->getEstablishment());

        $invitation->getCompany()?->removeInvitation($invitation);
        $this->orm->remove($invitation);
        $this->orm->flush();
    }

    public function findInvitation(string $token): CompanyInvitation
    {
        return $this->invitations->findOneValid(TokenHelper::hash($token), new DateTimeImmutable()) ?? throw new InvalidTokenException();
    }

    public function accept(string $token, User $user): CompanyMember
    {
        $invitation = $this->findInvitation($token);
        $company = $invitation->getCompany() ?? throw new InvalidTokenException();

        if (mb_strtolower((string) $user->getEmail()) !== $invitation->getEmail()) {
            throw new BusinessRuleException('Cette invitation a été envoyée à {email}. Connectez-vous avec cette adresse pour l\'accepter.', ['email' => $invitation->getEmail()]);
        }

        if ($this->members->findOneByCompanyAndUser($company, $user) !== null) {
            throw new BusinessRuleException('Vous faites déjà partie de {company}.', ['company' => $company->getName()]);
        }

        $member = (new CompanyMember())
            ->setRole($invitation->getRole())
            ->setStatus(MemberStatus::ACTIVE)
            ->setInvitedBy($invitation->getInvitedBy())
            ->setJoinedAt(new DateTimeImmutable());

        $company->addMember($member);
        $user->addMembership($member);
        $this->orm->persist($member);

        if ($invitation->getEstablishment() !== null && $invitation->getEstablishmentRole() !== null) {
            $role = (new EstablishmentMember())->setRole($invitation->getEstablishmentRole());
            $member->addEstablishmentRole($role);
            $invitation->getEstablishment()->addMember($role);
            $this->orm->persist($role);
        }

        $invitation->setAcceptedAt(new DateTimeImmutable());
        $user->setEmailVerifiedAt($user->getEmailVerifiedAt() ?? new DateTimeImmutable());
        $this->orm->flush();

        $this->events->dispatch(new MemberJoinedEvent($member));

        return $member;
    }

    public function changeRole(CompanyMember $actor, CompanyMember $member, MemberRoleInput $input): CompanyMember
    {
        $this->assertSameCompany($actor, $member);

        if ($member->getRole() === CompanyRole::OWNER) {
            throw new BusinessRuleException('Le rôle du propriétaire ne peut pas être modifié.');
        }

        if ($member === $actor) {
            throw new BusinessRuleException('Vous ne pouvez pas modifier votre propre rôle.');
        }

        $company = $member->getCompany();
        $establishment = $this->resolveEstablishment($company, $input->role, $input->establishmentId);
        $this->assertCanGrant($actor, $input->role, $establishment);

        $previous = $member->getRole()?->value;

        if (RoleHelper::isCompanyRole($input->role)) {
            $member->setRole(CompanyRole::from($input->role));
        } else {
            $existing = $this->resolver->findEstablishmentRole($member, $establishment);
            $previous ??= $existing?->getRole()->value ?? 'none';

            if ($member->getRole() === CompanyRole::ADMIN) {
                $this->assertCanGrant($actor, 'admin', null);
                $member->setRole(null);
            }

            if ($existing === null) {
                $existing = new EstablishmentMember();
                $member->addEstablishmentRole($existing);
                $establishment->addMember($existing);
                $this->orm->persist($existing);
            }

            $existing->setRole(RoleHelper::establishmentRole($input->role))->setCanManageBookings($input->canManageBookings);
        }

        $this->orm->flush();

        if ($previous !== $input->role) {
            $this->events->dispatch(new MemberRoleChangedEvent($member, (string) $previous, $input->role, $establishment));
        }

        return $member;
    }

    public function removeFromEstablishment(CompanyMember $actor, CompanyMember $member, CompanyEstablishment $establishment): CompanyMember
    {
        $this->assertSameCompany($actor, $member);

        if (!$this->resolver->memberCan($actor, Permission::MEMBERS_MANAGE, $establishment)) {
            throw new BusinessRuleException('Vous ne pouvez pas gérer l\'équipe de cet établissement.');
        }

        $role = $this->resolver->findEstablishmentRole($member, $establishment);

        if ($role !== null) {
            $member->removeEstablishmentRole($role);
            $establishment->removeMember($role);
            $this->orm->remove($role);
            $this->orm->flush();
        }

        return $member;
    }

    public function remove(CompanyMember $actor, CompanyMember $member): void
    {
        $this->assertSameCompany($actor, $member);

        if ($member->getRole() === CompanyRole::OWNER) {
            throw new BusinessRuleException('Le propriétaire ne peut pas être retiré de l\'entreprise.');
        }

        if ($member === $actor) {
            throw new BusinessRuleException('Vous ne pouvez pas vous retirer vous-même.');
        }

        if (!$this->resolver->isCompanyAdmin($actor)) {
            foreach ($member->getEstablishmentRoles() as $role) {
                if (!$this->resolver->memberCan($actor, Permission::MEMBERS_MANAGE, $role->getEstablishment())) {
                    throw new BusinessRuleException('Ce membre travaille aussi dans un établissement que vous ne gérez pas.');
                }
            }

            if ($member->getRole() !== null) {
                throw new BusinessRuleException('Seuls le propriétaire et les administrateurs peuvent retirer un administrateur.');
            }
        }

        $this->events->dispatch(new MemberRemovedEvent($member));

        $member->getCompany()?->removeMember($member);
        $member->getUser()?->removeMembership($member);
        $this->orm->remove($member);
        $this->orm->flush();
    }

    private function resolveEstablishment(?Company $company, string $role, ?int $establishmentId): ?CompanyEstablishment
    {
        if (RoleHelper::isCompanyRole($role)) {
            if ($role === CompanyRole::OWNER->value) {
                throw new BusinessRuleException('Il ne peut y avoir qu\'un seul propriétaire.');
            }

            return null;
        }

        if ($company === null || $establishmentId === null) {
            throw new BusinessRuleException('Choisissez l\'établissement de ce membre.');
        }

        return $this->establishments->findOneInCompany($company, $establishmentId) ?? throw new BusinessRuleException('Cet établissement n\'appartient pas à l\'entreprise.');
    }

    private function assertCanGrant(CompanyMember $actor, string $role, ?CompanyEstablishment $establishment): void
    {
        if (RoleHelper::isCompanyRole($role)) {
            if (!$this->resolver->isCompanyAdmin($actor)) {
                throw new BusinessRuleException('Seuls le propriétaire et les administrateurs peuvent nommer un administrateur.');
            }

            return;
        }

        if ($establishment === null || !$this->resolver->memberCan($actor, Permission::MEMBERS_MANAGE, $establishment)) {
            throw new BusinessRuleException('Vous ne pouvez pas gérer l\'équipe de cet établissement.');
        }

        if ($role === 'manager' && !$this->resolver->isCompanyAdmin($actor)) {
            throw new BusinessRuleException('Seuls le propriétaire et les administrateurs peuvent nommer un gérant.');
        }
    }

    private function assertSameCompany(CompanyMember $actor, CompanyMember $member): void
    {
        if ($actor->getCompany() === null || $actor->getCompany() !== $member->getCompany()) {
            throw new BusinessRuleException('Ce membre ne fait pas partie de votre entreprise.');
        }
    }
}