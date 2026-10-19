<?php

namespace App\Admin\Helper\View;

use App\User\Enum\UserRole;
use NeoPHP\Component\View\Contract\ViewFunctionInterface;
use NeoPHP\Package\Security\SecurityManagerInterface;

class AdminRoleViewHelper implements ViewFunctionInterface
{
    public const STAFF_ROLES = [UserRole::ADMIN, UserRole::MODERATOR, UserRole::SUPPORT];

    public function __construct(
        private SecurityManagerInterface $security,
    ) {
    }

    public function getName(): string
    {
        return 'admin_role';
    }

    public function __invoke(): ?string
    {
        foreach (self::STAFF_ROLES as $role) {
            if ($this->security->isGranted($role->value)) {
                return $role->label();
            }
        }

        return null;
    }
}