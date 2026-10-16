<?php

namespace App\Shared\Helper\View;

use NeoPHP\Component\Routing\RoutingManagerInterface;
use NeoPHP\Component\View\Contract\ViewFunctionInterface;

class RouteExistsViewHelper implements ViewFunctionInterface
{
    public function __construct(
        private RoutingManagerInterface $routing,
    ) {
    }

    public function getName(): string
    {
        return 'route_exists';
    }

    public function __invoke(string $name): bool
    {
        return $this->routing->getRoutes()->has($name);
    }
}