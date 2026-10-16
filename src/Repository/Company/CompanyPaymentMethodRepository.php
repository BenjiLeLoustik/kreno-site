<?php

namespace App\Repository\Company;

use App\Entity\Company\Company;
use App\Entity\Company\CompanyPaymentMethod;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CompanyPaymentMethodRepository extends AbstractRepository
{
    protected string $entityClass = CompanyPaymentMethod::class;

    public function findForCompany(Company $company): array
    {
        return $this->findBy(['company' => $company], ['defaultMethod' => 'DESC', 'createdAt' => 'ASC']);
    }

    public function findDefault(Company $company): ?CompanyPaymentMethod
    {
        return $this->findOneBy(['company' => $company, 'defaultMethod' => true]) ?? $this->findOneBy(['company' => $company], ['createdAt' => 'DESC']);
    }

    public function findOneForCompany(Company $company, int $id): ?CompanyPaymentMethod
    {
        return $this->findOneBy(['company' => $company, 'id' => $id]);
    }

    public function findOneByProviderReference(string $reference): ?CompanyPaymentMethod
    {
        return $this->findOneBy(['providerReference' => $reference]);
    }
}