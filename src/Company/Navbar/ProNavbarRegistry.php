<?php

namespace App\Company\Navbar;

use App\Company\Security\PermissionResolver;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Shared\Navbar\NavbarBuilderLoader;

class ProNavbarRegistry
{
    private ?array $builders = null;

    public function __construct(
        private NavbarBuilderLoader $loader,
        private PermissionResolver $resolver,
    ) {
    }

    public function items(CompanyMember $member, ?CompanyEstablishment $establishment): array
    {
        $items = [];

        foreach ($this->builders() as $builder) {
            if ($this->isAllowed($builder, $member, $establishment)) {
                $items[] = $this->item($builder, $establishment);
            }
        }

        return $items;
    }

    public function builders(): array
    {
        $this->builders ??= $this->loader->load(__DIR__, __NAMESPACE__, ProNavbarInterface::class);

        return $this->builders;
    }

    private function item(ProNavbarInterface $builder, ?CompanyEstablishment $establishment): array
    {
        return [
            'key' => $builder->getKey(),
            'label' => $builder->getLabel(),
            'route' => $builder->getRoute(),
            'icon' => $builder->getIcon(),
            'badge' => $this->badge($builder, $establishment),
        ];
    }

    private function badge(ProNavbarInterface $builder, ?CompanyEstablishment $establishment): int
    {
        if ($establishment === null) {
            return 0;
        }

        return $builder->getBadge($establishment);
    }

    private function isAllowed(
        ProNavbarInterface $builder,
        CompanyMember $member,
        ?CompanyEstablishment $establishment,
    ): bool {
        $permission = $builder->getPermission();

        if ($permission === null) {
            return true;
        }

        return match ($builder->getScope()) {
            ProNavbarScope::ESTABLISHMENT => $this->canOnEstablishment($member, $permission, $establishment),
            ProNavbarScope::COMPANY => $this->canOnCompany($member, $permission),
            ProNavbarScope::EITHER => $this->canOnCompany($member, $permission)
                || $this->canOnEstablishment($member, $permission, $establishment),
        };
    }

    private function canOnEstablishment(
        CompanyMember $member,
        string $permission,
        ?CompanyEstablishment $establishment,
    ): bool {
        if ($establishment === null) {
            return false;
        }

        return $this->resolver->memberCan($member, $permission, $establishment);
    }

    private function canOnCompany(CompanyMember $member, string $permission): bool
    {
        $company = $member->getCompany();

        if ($company === null) {
            return false;
        }

        return $this->resolver->memberCan($member, $permission, $company);
    }
}