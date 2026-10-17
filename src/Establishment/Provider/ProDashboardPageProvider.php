<?php

namespace App\Establishment\Provider;

use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;

class ProDashboardPageProvider
{
    public function __construct(
        private PermissionResolver $resolver,
        private ProDashboardProvider $dashboard,
    ) {
    }

    public function index(CompanyMember $member, CompanyEstablishment $establishment, User $user): array
    {
        return [
            'establishment' => [
                'id' => $establishment->getId(),
                'name' => $establishment->getName(),
                'status' => $establishment->getStatus()->value,
                'statusLabel' => $establishment->getStatus()->label(),
                'online' => $establishment->getStatus() === EstablishmentStatus::ONLINE,
                'rejectionReason' => $establishment->getRejectionReason(),
            ],
            'canPublish' => $this->resolver->memberCan($member, Permission::SLOTS_MANAGE, $establishment),
            'dashboard' => $this->dashboard->build($establishment, $user),
        ];
    }
}