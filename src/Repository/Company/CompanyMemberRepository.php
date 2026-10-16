<?php

namespace App\Repository\Company;

use App\Entity\Company\Company;
use App\Entity\Company\CompanyMember;
use App\Entity\User\User;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CompanyMemberRepository extends AbstractRepository
{
    protected string $entityClass = CompanyMember::class;

    public function findOneByCompanyAndUser(Company $company, User $user): ?CompanyMember
    {
        return $this->findOneBy(['company' => $company, 'user' => $user]);
    }

    public function findOneInCompany(Company $company, int $id): ?CompanyMember
    {
        return $this->findOneBy(['company' => $company, 'id' => $id]);
    }
}