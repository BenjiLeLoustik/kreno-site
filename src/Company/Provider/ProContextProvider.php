<?php

namespace App\Company\Provider;

use App\Company\Exception\EstablishmentInaccessibleException;
use App\Company\Navbar\ProNavbarRegistry;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Establishment\Enum\EstablishmentStatus;
use App\User\Helper\UserNameHelper;

class ProContextProvider
{
    public function __construct(
        private CurrentCompanyProvider $context,
        private PermissionResolver $resolver,
        private ProNavbarRegistry $navbar,
    ) {
    }

    public function build(CompanyMember $member): array
    {
        $current = $this->context->getCurrentEstablishment($member);

        return [
            'user' => $this->user($member, $current),
            'company' => ['id' => $member->getCompany()?->getId(), 'name' => $member->getCompany()?->getName()],
            'current' => $this->current($current),
            'establishments' => $this->establishments($member, $current),
            'permissions' => [
                'company' => $this->companyPermissions($member),
                'establishment' => $this->establishmentPermissions($member, $current),
            ],
            'nav' => $this->navbar->items($member, $current),
        ];
    }

    public function switchTo(CompanyMember $member, int $id): CompanyEstablishment
    {
        $establishment = $this->context->switchTo($member, $id);

        if ($establishment === null) {
            throw new EstablishmentInaccessibleException();
        }

        return $establishment;
    }

    private function user(CompanyMember $member, ?CompanyEstablishment $current): array
    {
        $user = $member->getUser();

        if ($user === null) {
            return ['name' => null, 'initials' => null, 'roleLabel' => $this->roleLabel($member, $current)];
        }

        return [
            'name' => UserNameHelper::fullName($user),
            'initials' => UserNameHelper::initials($user),
            'roleLabel' => $this->roleLabel($member, $current),
        ];
    }

    private function roleLabel(CompanyMember $member, ?CompanyEstablishment $current): ?string
    {
        if ($member->getRole() !== null) {
            return $member->getRole()->label();
        }

        if ($current === null) {
            return null;
        }

        return $this->resolver->findEstablishmentRole($member, $current)?->getRole()->label();
    }

    private function current(?CompanyEstablishment $current): ?array
    {
        if ($current === null) {
            return null;
        }

        return [
            'id' => $current->getId(),
            'name' => $current->getName(),
            'slug' => $current->getSlug(),
            'city' => $current->getCity()?->getSlug(),
            'online' => $current->getStatus() === EstablishmentStatus::ONLINE,
            'statusLabel' => $current->getStatus()->label(),
        ];
    }

    private function establishments(CompanyMember $member, ?CompanyEstablishment $current): array
    {
        $establishments = [];

        foreach ($this->context->getEstablishments($member) as $establishment) {
            $establishments[] = [
                'id' => $establishment->getId(),
                'name' => $establishment->getName(),
                'status' => $establishment->getStatus()->value,
                'current' => $establishment === $current,
            ];
        }

        return $establishments;
    }

    private function companyPermissions(CompanyMember $member): array
    {
        if ($member->getCompany() === null) {
            return [];
        }

        return $this->resolver->permissionsFor($member, $member->getCompany());
    }

    private function establishmentPermissions(CompanyMember $member, ?CompanyEstablishment $current): array
    {
        if ($current === null) {
            return [];
        }

        return $this->resolver->permissionsFor($member, $current);
    }
}