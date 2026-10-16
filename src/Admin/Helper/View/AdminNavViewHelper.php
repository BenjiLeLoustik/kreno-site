<?php

namespace App\Admin\Helper\View;

use App\Admin\Navbar\NavbarRegistry;
use NeoPHP\Component\View\Contract\ViewFunctionInterface;

class AdminNavViewHelper implements ViewFunctionInterface
{
    public function __construct(
        private NavbarRegistry $navbar,
    ) {
    }

    public function getName(): string
    {
        return 'admin_nav';
    }

    public function __invoke(): array
    {
        return $this->navbar->items();
    }
}