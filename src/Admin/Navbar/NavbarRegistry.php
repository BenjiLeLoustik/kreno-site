<?php

namespace App\Admin\Navbar;

use NeoPHP\Component\Container\ContainerManagerInterface;
use ReflectionClass;

class NavbarRegistry
{
    private ?array $builders = null;

    public function __construct(
        private ContainerManagerInterface $container,
    ) {
    }

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

    public function builders(): array
    {
        if ($this->builders !== null) {
            return $this->builders;
        }

        $builders = [];

        foreach (glob(__DIR__ . DIRECTORY_SEPARATOR . '*Builder.php') ?: [] as $file) {
            $class = __NAMESPACE__ . '\\' . basename($file, '.php');

            if (!class_exists($class) || !is_subclass_of($class, BuilderNavbarInterface::class) || !(new ReflectionClass($class))->isInstantiable()) {
                continue;
            }

            $builders[] = $this->container->get($class);
        }

        usort($builders, static fn (BuilderNavbarInterface $a, BuilderNavbarInterface $b): int => [$a->getPosition(), $a->getKey()] <=> [$b->getPosition(), $b->getKey()]);

        return $this->builders = $builders;
    }
}