<?php

namespace App\Admin\Registry;

use App\Admin\Navbar\BuilderNavbarInterface;

class NavbarRegistry extends AbstractBuilderRegistry
{
    public function items(): array
    {
        return array_map(static fn (BuilderNavbarInterface $builder): array => [
            'key' => $builder->getKey(),
            'label' => $builder->getLabel(),
            'route' => $builder->getRoute(),
            'role' => $builder->getRole(),
            'icon' => $builder->getIcon(),
            'badge' => $builder->getBadge(),
        ], $this->builders());
    }

    protected function directory(): string
    {
        return 'src/Admin/Navbar';
    }

    protected function namespace(): string
    {
        return 'App\\Admin\\Navbar';
    }

    protected function builderInterface(): string
    {
        return BuilderNavbarInterface::class;
    }
}