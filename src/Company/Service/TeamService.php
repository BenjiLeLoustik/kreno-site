<?php

namespace App\Company\Service;

use App\Company\Dto\InvitationInput;
use App\Company\Dto\MemberRoleInput;
use App\Company\Enum\CompanyRole;
use App\Company\Enum\MemberStatus;
use App\Company\Event\InvitationSentEvent;
use App\Company\Event\MemberJoinedEvent;
use App\Company\Event\MemberRemovedEvent;
use App\Company\Event\MemberRoleChangedEvent;
use App\Company\Exception\AdminGrantForbiddenException;
use App\Company\Exception\AdminRemovalForbiddenException;
use App\Company\Exception\AlreadyCompanyMemberException;
use App\Company\Exception\AlreadyTeamMemberException;
use App\Company\Exception\CompanyNotFoundException;
use App\Company\Exception\EstablishmentTeamForbiddenException;
use App\Company\Exception\ForeignEstablishmentException;
use App\Company\Exception\ForeignMemberException;
use App\Company\Exception\InvitationEmailMismatchException;
use App\Company\Exception\ManagerGrantForbiddenException;
use App\Company\Exception\MemberEstablishmentRequiredException;
use App\Company\Exception\MemberOutsideScopeException;
use App\Company\Exception\OwnerRemovalException;
use App\Company\Exception\OwnerRoleLockedException;
use App\Company\Exception\OwnRoleChangeException;
use App\Company\Exception\SelfRemovalException;
use App\Company\Exception\SingleOwnerException;
use App\Company\Helper\RoleHelper;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyInvitation;
use App\Entity\Company\CompanyMember;
use App\Entity\Establishment\EstablishmentMember;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentRole;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Repository\Company\CompanyInvitationRepository;
use App\Repository\Company\CompanyMemberRepository;
use App\User\Exception\InvalidTokenException;
use App\User\Helper\TokenHelper;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class TeamService
{
    public const NO_ROLE = 'none';

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
        $company = $actor->getCompany() ?? throw new CompanyNotFoundException();
        $email = mb_strtolower(trim($input->email));
        $establishment = $this->resolveEstablishment($company, $input->role, $input->establishmentId);

        $this->assertCanGrant($actor, $input->role, $establishment);
        $this->assertNotInTeam($company, $email);

        $now = new DateTimeImmutable();
        $this->invitations->findPendingForEmail($company, $email, $now)?->setExpiresAt($now);

        $token = TokenHelper::generate();
        $invitation = $this->createInvitation($actor, $input->role, $email, $establishment, $token);

        $company->addInvitation($invitation);
        $this->orm->persist($invitation);
        $this->orm->flush();

        $this->events->dispatch(new InvitationSentEvent($invitation, $token));

        return $invitation;
    }

    public function revoke(CompanyMember $actor, CompanyInvitation $invitation): void
    {
        $this->assertCanGrant($actor, $this->invitationRole($invitation), $invitation->getEstablishment());

        $invitation->getCompany()?->removeInvitation($invitation);
        $this->orm->remove($invitation);
        $this->orm->flush();
    }

    public function findInvitation(string $token): CompanyInvitation
    {
        $invitation = $this->invitations->findOneValid(TokenHelper::hash($token), new DateTimeImmutable());

        return $invitation ?? throw new InvalidTokenException();
    }

    public function accept(string $token, User $user): CompanyMember
    {
        $invitation = $this->findInvitation($token);
        $company = $invitation->getCompany() ?? throw new InvalidTokenException();

        if (mb_strtolower((string) $user->getEmail()) !== $invitation->getEmail()) {
            throw new InvitationEmailMismatchException((string) $invitation->getEmail());
        }

        if ($this->members->findOneByCompanyAndUser($company, $user) !== null) {
            throw new AlreadyCompanyMemberException((string) $company->getName());
        }

        $member = (new CompanyMember())
            ->setRole($invitation->getRole())
            ->setStatus(MemberStatus::ACTIVE)
            ->setInvitedBy($invitation->getInvitedBy())
            ->setJoinedAt(new DateTimeImmutable());

        $company->addMember($member);
        $user->addMembership($member);
        $this->orm->persist($member);
        $this->addInvitedEstablishmentRole($member, $invitation);

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
            throw new OwnerRoleLockedException();
        }

        if ($member === $actor) {
            throw new OwnRoleChangeException();
        }

        $establishment = $this->resolveEstablishment($member->getCompany(), $input->role, $input->establishmentId);
        $this->assertCanGrant($actor, $input->role, $establishment);

        $previous = $member->getRole()?->value;

        if (RoleHelper::isCompanyRole($input->role)) {
            $member->setRole(CompanyRole::from($input->role));
        } else {
            $existing = $this->resolver->findEstablishmentRole($member, $establishment);
            $previous ??= $existing?->getRole()->value ?? self::NO_ROLE;

            $this->demoteAdmin($actor, $member);
            $this->setEstablishmentRole($member, $establishment, $existing, $input);
        }

        $this->orm->flush();

        if ($previous !== $input->role) {
            $event = new MemberRoleChangedEvent($member, (string) $previous, $input->role, $establishment);
            $this->events->dispatch($event);
        }

        return $member;
    }

    public function removeFromEstablishment(
        CompanyMember $actor,
        CompanyMember $member,
        CompanyEstablishment $establishment,
    ): CompanyMember {
        $this->assertSameCompany($actor, $member);

        if (!$this->resolver->memberCan($actor, Permission::MEMBERS_MANAGE, $establishment)) {
            throw new EstablishmentTeamForbiddenException();
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
            throw new OwnerRemovalException();
        }

        if ($member === $actor) {
            throw new SelfRemovalException();
        }

        if (!$this->resolver->isCompanyAdmin($actor)) {
            $this->assertCanRemoveAsManager($actor, $member);
        }

        $this->events->dispatch(new MemberRemovedEvent($member));

        $member->getCompany()?->removeMember($member);
        $member->getUser()?->removeMembership($member);
        $this->orm->remove($member);
        $this->orm->flush();
    }

    private function assertNotInTeam(Company $company, string $email): void
    {
        foreach ($company->getMembers() as $member) {
            if ($member->getUser()?->getEmail() === $email) {
                throw new AlreadyTeamMemberException($email);
            }
        }
    }

    private function createInvitation(
        CompanyMember $actor,
        string $role,
        string $email,
        ?CompanyEstablishment $establishment,
        string $token,
    ): CompanyInvitation {
        return (new CompanyInvitation())
            ->setEmail($email)
            ->setRole(CompanyRole::tryFrom($role))
            ->setEstablishment($establishment)
            ->setEstablishmentRole(RoleHelper::establishmentRole($role))
            ->setInvitedBy($actor->getUser())
            ->setTokenHash(TokenHelper::hash($token))
            ->setExpiresAt(new DateTimeImmutable(self::INVITATION_LIFETIME));
    }

    private function invitationRole(CompanyInvitation $invitation): string
    {
        return $invitation->getRole()?->value
            ?? $invitation->getEstablishmentRole()?->value
            ?? EstablishmentRole::EMPLOYEE->value;
    }

    private function addInvitedEstablishmentRole(CompanyMember $member, CompanyInvitation $invitation): void
    {
        $establishment = $invitation->getEstablishment();
        $role = $invitation->getEstablishmentRole();

        if ($establishment === null || $role === null) {
            return;
        }

        $establishmentMember = (new EstablishmentMember())->setRole($role);
        $member->addEstablishmentRole($establishmentMember);
        $establishment->addMember($establishmentMember);
        $this->orm->persist($establishmentMember);
    }

    private function demoteAdmin(CompanyMember $actor, CompanyMember $member): void
    {
        if ($member->getRole() !== CompanyRole::ADMIN) {
            return;
        }

        $this->assertCanGrant($actor, CompanyRole::ADMIN->value, null);
        $member->setRole(null);
    }

    private function setEstablishmentRole(
        CompanyMember $member,
        CompanyEstablishment $establishment,
        ?EstablishmentMember $existing,
        MemberRoleInput $input,
    ): void {
        if ($existing === null) {
            $existing = new EstablishmentMember();
            $member->addEstablishmentRole($existing);
            $establishment->addMember($existing);
            $this->orm->persist($existing);
        }

        $existing
            ->setRole(RoleHelper::establishmentRole($input->role))
            ->setCanManageBookings($input->canManageBookings);
    }

    private function assertCanRemoveAsManager(CompanyMember $actor, CompanyMember $member): void
    {
        foreach ($member->getEstablishmentRoles() as $role) {
            if (!$this->resolver->memberCan($actor, Permission::MEMBERS_MANAGE, $role->getEstablishment())) {
                throw new MemberOutsideScopeException();
            }
        }

        if ($member->getRole() !== null) {
            throw new AdminRemovalForbiddenException();
        }
    }

    private function resolveEstablishment(
        ?Company $company,
        string $role,
        ?int $establishmentId
    ): ?CompanyEstablishment {
        if (RoleHelper::isCompanyRole($role)) {
            if ($role === CompanyRole::OWNER->value) {
                throw new SingleOwnerException();
            }

            return null;
        }

        if ($company === null || $establishmentId === null) {
            throw new MemberEstablishmentRequiredException();
        }

        return $this->establishments->findOneInCompany($company, $establishmentId)
            ?? throw new ForeignEstablishmentException();
    }

    private function assertCanGrant(CompanyMember $actor, string $role, ?CompanyEstablishment $establishment): void
    {
        if (RoleHelper::isCompanyRole($role)) {
            if (!$this->resolver->isCompanyAdmin($actor)) {
                throw new AdminGrantForbiddenException();
            }

            return;
        }

        if ($establishment === null) {
            throw new EstablishmentTeamForbiddenException();
        }

        if (!$this->resolver->memberCan($actor, Permission::MEMBERS_MANAGE, $establishment)) {
            throw new EstablishmentTeamForbiddenException();
        }

        if ($role === EstablishmentRole::MANAGER->value && !$this->resolver->isCompanyAdmin($actor)) {
            throw new ManagerGrantForbiddenException();
        }
    }

    private function assertSameCompany(CompanyMember $actor, CompanyMember $member): void
    {
        if ($actor->getCompany() === null || $actor->getCompany() !== $member->getCompany()) {
            throw new ForeignMemberException();
        }
    }
}