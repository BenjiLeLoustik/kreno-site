<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Shared\Exception\BusinessRuleException;
use App\User\Helper\UserNameHelper;
use NeoPHP\Component\Controller\Contract\AbstractController;

abstract class AbstractProController extends AbstractController
{
    public function __construct(
        protected CurrentCompanyProvider $context,
        protected PermissionResolver $resolver,
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

    protected function currentMember(): CompanyMember
    {
        return $this->context->getMembership($this->currentUser()) ?? throw $this->createAccessDeniedException('Votre compte n\'est rattaché à aucune entreprise.');
    }

    protected function currentCompany(): Company
    {
        return $this->currentMember()->getCompany() ?? throw $this->createAccessDeniedException();
    }

    protected function currentEstablishment(): CompanyEstablishment
    {
        return $this->context->getCurrentEstablishment($this->currentMember()) ?? throw $this->createNotFoundException('Aucun établissement accessible.');
    }

    protected function requirePermission(string $permission, Company|CompanyEstablishment $subject): CompanyMember
    {
        $member = $this->currentMember();

        if (!$this->resolver->memberCan($member, $permission, $subject)) {
            throw $this->createAccessDeniedException('Votre rôle ne permet pas cette action.');
        }

        return $member;
    }

    protected function proContext(): array
    {
        $member = $this->currentMember();
        $user = $member->getUser();
        $current = $this->context->getCurrentEstablishment($member);

        return [
            'user' => [
                'name' => $user !== null ? UserNameHelper::fullName($user) : null,
                'initials' => $user !== null ? UserNameHelper::initials($user) : null,
                'roleLabel' => $member->getRole()?->label() ?? ($current !== null ? $this->resolver->findEstablishmentRole($member, $current)?->getRole()->label() : null),
            ],
            'company' => ['id' => $member->getCompany()?->getId(), 'name' => $member->getCompany()?->getName()],
            'current' => $current === null ? null : [
                'id' => $current->getId(),
                'name' => $current->getName(),
                'slug' => $current->getSlug(),
                'city' => $current->getCity()?->getSlug(),
                'online' => $current->getStatus() === EstablishmentStatus::ONLINE,
                'statusLabel' => $current->getStatus()->label(),
            ],
            'establishments' => array_map(static fn (CompanyEstablishment $establishment): array => [
                'id' => $establishment->getId(),
                'name' => $establishment->getName(),
                'status' => $establishment->getStatus()->value,
                'current' => $establishment === $current,
            ], $this->context->getEstablishments($member)),
            'permissions' => [
                'company' => $member->getCompany() !== null ? $this->resolver->permissionsFor($member, $member->getCompany()) : [],
                'establishment' => $current !== null ? $this->resolver->permissionsFor($member, $current) : [],
            ],
        ];
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