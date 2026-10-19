<?php

namespace App\Shared\Navbar;

use NeoPHP\Component\Container\ContainerManagerInterface;
use ReflectionClass;

class NavbarBuilderLoader
{
    public function __construct(
        private ContainerManagerInterface $container,
    ) {
    }

    public function load(string $directory, string $namespace, string $interface): array
    {
        $builders = [];

        foreach (glob($directory . DIRECTORY_SEPARATOR . '*Builder.php') ?: [] as $file) {
            $class = $namespace . '\\' . basename($file, '.php');

            if ($this->isBuilder($class, $interface)) {
                $builders[] = $this->container->get($class);
            }
        }

        usort($builders, self::compare(...));

        return $builders;
    }

    private static function compare(NavbarBuilderInterface $a, NavbarBuilderInterface $b): int
    {
        return [$a->getPosition(), $a->getKey()] <=> [$b->getPosition(), $b->getKey()];
    }

    private function isBuilder(string $class, string $interface): bool
    {
        if (!class_exists($class) || !is_subclass_of($class, $interface)) {
            return false;
        }

        return (new ReflectionClass($class))->isInstantiable();
    }
}