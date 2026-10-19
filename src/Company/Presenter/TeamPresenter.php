<?php

namespace App\Company\Presenter;

use App\Company\Enum\CompanyRole;
use App\Company\Enum\TeamMatrixAction;
use App\Company\Helper\RoleHelper;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\CompanyInvitation;
use App\Entity\Company\CompanyMember;
use App\Entity\Establishment\EstablishmentMember;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentRole;
use App\Shared\Helper\DateHelper;
use App\User\Helper\UserNameHelper;

class TeamPresenter
{
    public function __construct(
        private PermissionResolver $resolver
    ) {
    }

    public function member(CompanyMember $member, ?CompanyMember $viewer = null): array
    {
        $user = $member->getUser();
        $companyRole = $member->getRole();
        $scope = $this->scope($member, $viewer);

        return [
            'id' => $member->getId(),
            'name' => $this->fullName($user),
            'initials' => $this->initials($user),
            'email' => $user?->getEmail(),
            'companyRole' => $companyRole?->value,
            'roleLabel' => $this->memberRoleLabel($companyRole, $scope),
            'allEstablishments' => $companyRole !== null,
            'scope' => $scope,
            'status' => $member->getStatus()->value,
            'statusLabel' => $member->getStatus()->label(),
            'isOwner' => $companyRole === CompanyRole::OWNER,
            'isYou' => $viewer === $member,
            'joinedAt' => DateHelper::iso($member->getJoinedAt()),
        ];
    }

    public function invitation(CompanyInvitation $invitation): array
    {
        $role = $invitation->getRole()?->value
            ?? $invitation->getEstablishmentRole()?->value
            ?? EstablishmentRole::EMPLOYEE->value;

        return [
            'id' => $invitation->getId(),
            'email' => $invitation->getEmail(),
            'role' => $role,
            'roleLabel' => RoleHelper::label($role),
            'establishment' => $invitation->getEstablishment()?->getName(),
            'invitedBy' => $this->shortName($invitation->getInvitedBy()),
            'expiresAt' => DateHelper::iso($invitation->getExpiresAt()),
            'createdAt' => DateHelper::iso($invitation->getCreatedAt()),
        ];
    }

    public function roleMatrix(): array
    {
        $columns = [];
        $rows = [];

        foreach ($this->roles() as $role) {
            $columns[] = [
                'key' => $role,
                'label' => RoleHelper::label($role)
            ];
        }

        foreach (TeamMatrixAction::cases() as $action) {
            $cells = [];

            foreach ($this->roles() as $role) {
                $cells[$role] = $this->cell($role, $action->value);
            }

            $rows[] = [
                'action' => $action->label(),
                'cells' => $cells
            ];
        }

        return [
            'columns' => $columns,
            'rows' => $rows
        ];
    }

    private function scope(CompanyMember $member, ?CompanyMember $viewer): array
    {
        $scope = [];

        foreach ($member->getEstablishmentRoles() as $role) {
            $scope[] = [
                'establishmentId' => $role->getEstablishment()?->getId(),
                'establishment' => $role->getEstablishment()?->getName(),
                'role' => $role->getRole()->value,
                'roleLabel' => $role->getRole()->label(),
                'canManageBookings' => $role->isCanManageBookings(),
                'manageable' => $this->isManageable($role, $viewer),
            ];
        }

        return $scope;
    }

    private function isManageable(EstablishmentMember $role, ?CompanyMember $viewer): bool
    {
        $establishment = $role->getEstablishment();

        if ($viewer === null || $establishment === null) {
            return false;
        }

        return $this->resolver->memberCan($viewer, Permission::MEMBERS_MANAGE, $establishment);
    }

    private function memberRoleLabel(?CompanyRole $companyRole, array $scope): string
    {
        if ($companyRole !== null) {
            return $companyRole->label();
        }

        return $scope[0]['roleLabel'] ?? EstablishmentRole::EMPLOYEE->label();
    }

    private function fullName(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return UserNameHelper::fullName($user);
    }

    private function shortName(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return UserNameHelper::shortName($user);
    }

    private function initials(?User $user): string
    {
        if ($user === null) {
            return '?';
        }

        return UserNameHelper::initials($user);
    }

    private function roles(): array
    {
        $roles = [];

        foreach ([...CompanyRole::cases(), ...EstablishmentRole::cases()] as $role) {
            $roles[] = $role->value;
        }

        return $roles;
    }

    private function cell(string $role, string $permission): string
    {
        if (RoleHelper::isCompanyRole($role)) {
            return 'yes';
        }

        if (in_array($permission, Permission::ESTABLISHMENT_ROLES[$role], true)) {
            return $this->grantedCell($permission);
        }

        if ((Permission::ON_DEMAND[$role] ?? null) === $permission) {
            return 'on_demand';
        }

        return 'no';
    }

    private function grantedCell(string $permission): string
    {
        if ($permission === Permission::MEMBERS_MANAGE) {
            return 'scope';
        }

        return 'yes';
    }
}