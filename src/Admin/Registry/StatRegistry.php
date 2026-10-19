<?php

namespace App\Admin\Registry;

use App\Admin\Dashboard\BuilderStatInterface;
use DateTimeImmutable;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Container\ContainerManagerInterface;
use NeoPHP\Package\Security\SecurityManagerInterface;

class StatRegistry extends AbstractBuilderRegistry
{
    public function __construct(
        ContainerManagerInterface $container,
        private SecurityManagerInterface $security,
        #[Autowire(param: 'kernel.root_path')]
        string $rootPath = '',
    ) {
        parent::__construct($container, $rootPath);
    }

    public function items(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $items = [];

        foreach ($this->builders() as $builder) {
            if (!$builder instanceof BuilderStatInterface || !$this->security->isGranted($builder->getRole())) {
                continue;
            }

            $stat = $builder->build($now);
            $items[] = [
                'key' => $builder->getKey(),
                'label' => $builder->getLabel(),
                'value' => $stat->getValue(),
                'note' => $stat->getNote(),
                'alert' => $stat->isAlert(),
                'route' => $builder->getRoute(),
            ];
        }

        return $items;
    }

    protected function directory(): string
    {
        return 'src/Admin/Dashboard';
    }

    protected function namespace(): string
    {
        return 'App\\Admin\\Dashboard';
    }

    protected function builderInterface(): string
    {
        return BuilderStatInterface::class;
    }
}