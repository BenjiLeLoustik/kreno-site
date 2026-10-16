<?php

namespace App\Catalog\Helper\View;

use App\Catalog\Provider\NavigationProvider;
use NeoPHP\Component\View\Contract\ViewFunctionInterface;

class NavigationHelper implements ViewFunctionInterface
{
    public function __construct(
        private NavigationProvider $navigation,
    ) {
    }

    public function getName(): string
    {
        return 'navigation';
    }

    public function __invoke(): array
    {
        return $this->navigation->get();
    }
}