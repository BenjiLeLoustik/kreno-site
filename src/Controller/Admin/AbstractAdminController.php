<?php

namespace App\Controller\Admin;

use App\Entity\User\User;
use App\Shared\Exception\BusinessRuleException;
use App\User\Enum\UserRole;
use NeoPHP\Component\Controller\Contract\AbstractController;

abstract class AbstractAdminController extends AbstractController
{
    protected function currentAdmin(UserRole $role = UserRole::ADMIN): User
    {
        $this->denyAccessUnlessGranted($role->value);

        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    protected function invalid(object $input): bool
    {
        $error = $this->validate($input)->first();

        if ($error === null) {
            return false;
        }

        $this->addFlash('error', $error);

        return true;
    }

    protected function attempt(callable $action, ?string $success = null): bool
    {
        try {
            $action();
        } catch (BusinessRuleException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return false;
        }

        if ($success !== null) {
            $this->addFlash('success', $success);
        }

        return true;
    }
}