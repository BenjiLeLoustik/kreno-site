<?php

namespace App\Controller\Front;

use App\Entity\User\User;
use App\Shared\Exception\BusinessRuleException;
use NeoPHP\Component\Controller\Contract\AbstractController;

abstract class AbstractFrontController extends AbstractController
{
    protected function currentUser(): User
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    protected function optionalUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
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