<?php

namespace App\Admin\Registry;

use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Container\ContainerManagerInterface;
use ReflectionClass;

abstract class AbstractBuilderRegistry
{
    private ?array $builders = null;

    public function __construct(
        protected ContainerManagerInterface $container,
        #[Autowire(param: 'kernel.root_path')]
        protected string $rootPath = '',
    ) {
    }

    abstract protected function directory(): string;

    abstract protected function namespace(): string;

    abstract protected function builderInterface(): string;

    public function builders(): array
    {
        if ($this->builders !== null) {
            return $this->builders;
        }

        $builders = array_map(fn (string $class): object => $this->container->get($class), $this->classes());

        if ($builders === []) {
            error_log(sprintf('[%s] Aucun builder trouvé dans %s', static::class, $this->path()));
        }

        usort($builders, static fn (object $a, object $b): int => [$a->getPosition(), $a->getKey()] <=> [$b->getPosition(), $b->getKey()]);

        return $this->builders = $builders;
    }

    protected function path(): string
    {
        return rtrim($this->rootPath, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, trim($this->directory(), '/'));
    }

    private function classes(): array
    {
        $path = $this->path();
        $classes = [];

        if (!is_dir($path)) {
            return [];
        }

        foreach (scandir($path) ?: [] as $file) {
            if (!str_ends_with($file, 'Builder.php')) {
                continue;
            }

            $class = rtrim($this->namespace(), '\\') . '\\' . substr($file, 0, -4);

            if (!class_exists($class)) {
                require_once $path . DIRECTORY_SEPARATOR . $file;
            }

            if (class_exists($class, false) && (new ReflectionClass($class))->isInstantiable() && is_subclass_of($class, $this->builderInterface())) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}