<?php

namespace App\Controller\Pro;

use App\Company\Exception\CompanyRequiredException;
use App\Company\Exception\NoAccessibleEstablishmentException;
use App\Company\Exception\NoCompanyMembershipException;
use App\Company\Exception\RoleNotAllowedException;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\User\User;
use App\Shared\Exception\BusinessRuleException;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Response\Response;

abstract class AbstractProController extends AbstractController
{
    public function __construct(
        protected CurrentCompanyProvider $context,
        protected PermissionResolver $resolver,
        protected ProContextProvider $pro,
    ) {
    }

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

        if (!$user instanceof User) {
            return null;
        }

        return $user;
    }

    protected function currentMember(): CompanyMember
    {
        $member = $this->context->getMembership($this->currentUser());

        if ($member === null) {
            throw new NoCompanyMembershipException();
        }

        return $member;
    }

    protected function currentCompany(): Company
    {
        $company = $this->currentMember()->getCompany();

        if ($company === null) {
            throw new CompanyRequiredException();
        }

        return $company;
    }

    protected function currentEstablishment(): CompanyEstablishment
    {
        $establishment = $this->context->getCurrentEstablishment($this->currentMember());

        if ($establishment === null) {
            throw new NoAccessibleEstablishmentException();
        }

        return $establishment;
    }

    protected function requirePermission(string $permission, Company|CompanyEstablishment $subject): CompanyMember
    {
        $member = $this->currentMember();

        if (!$this->resolver->memberCan($member, $permission, $subject)) {
            throw new RoleNotAllowedException();
        }

        return $member;
    }

    protected function permittedEstablishment(string $permission): CompanyEstablishment
    {
        $establishment = $this->currentEstablishment();
        $this->requirePermission($permission, $establishment);

        return $establishment;
    }

    protected function permittedCompany(string $permission): Company
    {
        $company = $this->currentCompany();
        $this->requirePermission($permission, $company);

        return $company;
    }

    protected function proContext(): array
    {
        return $this->pro->build($this->currentMember());
    }

    protected function renderPro(string $view, array $parameters = []): Response
    {
        return $this->render($view, ['context' => $this->proContext()] + $parameters);
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