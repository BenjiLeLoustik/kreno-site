<?php

namespace App\Repository\Company;

use App\Entity\Company\Company;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CompanyRepository extends AbstractRepository
{
    protected string $entityClass = Company::class;

    public function sirenExists(string $siren, ?Company $except = null): bool
    {
        $company = $this->findOneBy(['siren' => $siren]);

        return $company !== null && $company !== $except;
    }
}