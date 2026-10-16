<?php

namespace App\Company\Presenter;

use App\Company\Helper\RoleHelper;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\CompanyInvitation;
use App\Entity\Company\CompanyMember;
use App\Shared\Helper\DateHelper;
use App\User\Helper\UserNameHelper;

class TeamPresenter
{
    private const MATRIX = [
        ['Modifier l\'entreprise', Permission::COMPANY_EDIT],
        ['Créer un établissement', Permission::ESTABLISHMENT_CREATE],
        ['Gérer les membres', Permission::MEMBERS_MANAGE],
        ['Gérer les prestations', Permission::SERVICES_MANAGE],
        ['Gérer les créneaux', Permission::SLOTS_MANAGE],
        ['Gérer les réservations', Permission::BOOKINGS_MANAGE],
        ['Consulter le planning', Permission::PLANNING_VIEW],
    ];

    public function __construct(private PermissionResolver $resolver)
    {
    }

    public function member(CompanyMember $member, ?CompanyMember $viewer = null): array
    {
        $user = $member->getUser();
        $scope = [];

        foreach ($member->getEstablishmentRoles() as $role) {
            $scope[] = [
                'establishmentId' => $role->getEstablishment()?->getId(),
                'establishment' => $role->getEstablishment()?->getName(),
                'role' => $role->getRole()->value,
                'roleLabel' => $role->getRole()->label(),
                'canManageBookings' => $role->isCanManageBookings(),
                'manageable' => $viewer !== null && $role->getEstablishment() !== null && $this->resolver->memberCan($viewer, Permission::MEMBERS_MANAGE, $role->getEstablishment()),
            ];
        }

        $companyRole = $member->getRole()?->value;

        return [
            'id' => $member->getId(),
            'name' => $user !== null ? UserNameHelper::fullName($user) : null,
            'initials' => $user !== null ? UserNameHelper::initials($user) : '?',
            'email' => $user?->getEmail(),
            'companyRole' => $companyRole,
            'roleLabel' => $companyRole !== null ? RoleHelper::label($companyRole) : ($scope[0]['roleLabel'] ?? 'Employé'),
            'allEstablishments' => $companyRole !== null,
            'scope' => $scope,
            'status' => $member->getStatus()->value,
            'statusLabel' => $member->getStatus()->label(),
            'isOwner' => $companyRole === 'owner',
            'isYou' => $viewer === $member,
            'joinedAt' => DateHelper::iso($member->getJoinedAt()),
        ];
    }

    public function invitation(CompanyInvitation $invitation): array
    {
        $role = $invitation->getRole()?->value ?? $invitation->getEstablishmentRole()?->value ?? 'employee';

        return [
            'id' => $invitation->getId(),
            'email' => $invitation->getEmail(),
            'role' => $role,
            'roleLabel' => RoleHelper::label($role),
            'establishment' => $invitation->getEstablishment()?->getName(),
            'invitedBy' => $invitation->getInvitedBy() !== null ? UserNameHelper::shortName($invitation->getInvitedBy()) : null,
            'expiresAt' => DateHelper::iso($invitation->getExpiresAt()),
            'createdAt' => DateHelper::iso($invitation->getCreatedAt()),
        ];
    }

    public function roleMatrix(): array
    {
        $columns = ['owner', 'admin', 'manager', 'planner', 'receptionist', 'employee'];
        $rows = [];

        foreach (self::MATRIX as [$label, $permission]) {
            $cells = [];

            foreach ($columns as $column) {
                if (in_array($column, ['owner', 'admin'], true)) {
                    $cells[$column] = 'yes';
                    continue;
                }

                $granted = in_array($permission, Permission::ESTABLISHMENT_ROLES[$column], true);
                $onDemand = (Permission::ON_DEMAND[$column] ?? null) === $permission;
                $cells[$column] = $granted ? ($permission === Permission::MEMBERS_MANAGE ? 'scope' : 'yes') : ($onDemand ? 'on_demand' : 'no');
            }

            $rows[] = ['action' => $label, 'cells' => $cells];
        }

        return ['columns' => array_map(static fn (string $role): array => ['key' => $role, 'label' => RoleHelper::label($role)], $columns), 'rows' => $rows];
    }
}