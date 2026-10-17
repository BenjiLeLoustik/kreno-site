<?php

namespace App\Company\Provider;

use App\Company\Exception\InvitationNotFoundException;
use App\Company\Exception\MemberNotFoundException;
use App\Company\Exception\TeamManagementDeniedException;
use App\Company\Mapper\TeamRequestMapper;
use App\Company\Presenter\TeamPresenter;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyInvitation;
use App\Entity\Company\CompanyMember;
use App\Establishment\Exception\EstablishmentNotFoundException;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Repository\Company\CompanyInvitationRepository;
use App\Repository\Company\CompanyMemberRepository;
use DateTimeImmutable;

class TeamPageProvider
{
    public function __construct(
        private CurrentCompanyProvider $context,
        private PermissionResolver $resolver,
        private CompanyMemberRepository $members,
        private CompanyInvitationRepository $invitations,
        private CompanyEstablishmentRepository $establishments,
        private TeamPresenter $presenter,
    ) {
    }

    public function index(CompanyMember $viewer, Company $company, string $filter): array
    {
        $manageable = $this->manageable($viewer);

        if ($manageable === [] && !$this->resolver->isCompanyAdmin($viewer)) {
            throw new TeamManagementDeniedException();
        }

        return [
            'filter' => $filter,
            'members' => $this->members($viewer, $company, $filter),
            'invitations' => $this->pendingInvitations($company),
            'canInviteAdmins' => $this->resolver->isCompanyAdmin($viewer),
            'establishments' => $this->options($this->context->getEstablishments($viewer)),
            'manageableEstablishments' => $this->options($manageable),
            'roleMatrix' => $this->presenter->roleMatrix(),
        ];
    }

    public function invitation(Company $company, int $id): CompanyInvitation
    {
        $invitation = $this->invitations->find($id);

        if (!$invitation instanceof CompanyInvitation || $invitation->getCompany() !== $company) {
            throw new InvitationNotFoundException();
        }

        return $invitation;
    }

    public function member(Company $company, int $id): CompanyMember
    {
        $member = $this->members->findOneInCompany($company, $id);

        if ($member === null) {
            throw new MemberNotFoundException();
        }

        return $member;
    }

    public function establishment(Company $company, int $id): CompanyEstablishment
    {
        $establishment = $this->establishments->findOneInCompany($company, $id);

        if ($establishment === null) {
            throw new EstablishmentNotFoundException();
        }

        return $establishment;
    }

    private function manageable(CompanyMember $viewer): array
    {
        $manageable = [];

        foreach ($this->context->getEstablishments($viewer) as $establishment) {
            if ($this->resolver->memberCan($viewer, Permission::MEMBERS_MANAGE, $establishment)) {
                $manageable[] = $establishment;
            }
        }

        return $manageable;
    }

    private function members(CompanyMember $viewer, Company $company, string $filter): array
    {
        $members = [];

        foreach ($company->getMembers() as $member) {
            if ($this->inFilter($member, $filter)) {
                $members[] = $this->presenter->member($member, $viewer);
            }
        }

        return $members;
    }

    private function inFilter(CompanyMember $member, string $filter): bool
    {
        if ($filter === TeamRequestMapper::ALL_ESTABLISHMENTS || $member->getRole() !== null) {
            return true;
        }

        foreach ($member->getEstablishmentRoles() as $role) {
            if ((string) $role->getEstablishment()?->getId() === $filter) {
                return true;
            }
        }

        return false;
    }

    private function pendingInvitations(Company $company): array
    {
        $invitations = [];

        foreach ($this->invitations->findPendingForCompany($company, new DateTimeImmutable()) as $invitation) {
            $invitations[] = $this->presenter->invitation($invitation);
        }

        return $invitations;
    }

    private function options(array $establishments): array
    {
        $options = [];

        foreach ($establishments as $establishment) {
            $options[] = ['id' => $establishment->getId(), 'name' => $establishment->getName()];
        }

        return $options;
    }
}