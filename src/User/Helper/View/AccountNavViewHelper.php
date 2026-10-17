<?php

namespace App\User\Helper\View;

use App\Entity\User\User;
use App\User\Navbar\AccountNavbarRegistry;
use NeoPHP\Component\View\Contract\ViewFunctionInterface;
use NeoPHP\Package\Security\SecurityManagerInterface;

class AccountNavViewHelper implements ViewFunctionInterface
{
    public function __construct(
        private AccountNavbarRegistry $navbar,
        private SecurityManagerInterface $security,
    ) {
    }

    public function getName(): string
    {
        return 'account_nav';
    }

    public function __invoke(): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return [];
        }

        return $this->navbar->items($user);
    }
}