<?php

namespace App\Company\Security\Voter;

use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use NeoPHP\Package\Security\Attribute\AsVoter;
use NeoPHP\Package\Security\Contract\AbstractVoter;
use NeoPHP\Package\Security\Contract\TokenInterface;

#[AsVoter]
class CompanyPermissionVoter extends AbstractVoter
{
    public function __construct(
        private PermissionResolver $resolver
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, Permission::ALL, true)
            && ($subject instanceof Company || $subject instanceof CompanyEstablishment);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        return $user instanceof User && $this->resolver->can($user, $attribute, $subject);
    }
}