<?php

namespace App\Controller\Admin;

use App\Entity\User\User;
use App\Entity\Website\WebsiteConfig;
use App\Shared\Exception\BusinessRuleException;
use App\User\Enum\UserRole;
use App\Website\Provider\WebsiteProvider;
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

    protected function findVersion(WebsiteProvider $provider, ?string $code): WebsiteConfig
    {
        return $provider->getVersion($code) ?? throw $this->createNotFoundException('La version {code} n\'existe pas.', ['code' => $code ?? 'en ligne']);
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

    protected function attempt(callable $action, string $success): bool
    {
        try {
            $action();
        } catch (BusinessRuleException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return false;
        }

        $this->addFlash('success', $success);

        return true;
    }
}