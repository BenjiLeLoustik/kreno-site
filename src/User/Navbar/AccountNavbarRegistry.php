<?php

namespace App\User\Navbar;

use App\Entity\User\User;
use App\Shared\Navbar\NavbarBuilderLoader;

class AccountNavbarRegistry
{
    private ?array $builders = null;

    public function __construct(
        private NavbarBuilderLoader $loader,
    ) {
    }

    public function items(User $user): array
    {
        return array_map(static fn (AccountNavbarInterface $builder): array => [
            'key' => $builder->getKey(),
            'label' => $builder->getLabel(),
            'route' => $builder->getRoute(),
            'icon' => $builder->getIcon(),
            'badge' => $builder->getBadge($user),
        ], $this->builders());
    }

    public function builders(): array
    {
        $this->builders ??= $this->loader->load(__DIR__, __NAMESPACE__, AccountNavbarInterface::class);

        return $this->builders;
    }
}