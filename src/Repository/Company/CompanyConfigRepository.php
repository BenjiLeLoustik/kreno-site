<?php

namespace App\Repository\Company;

use App\Entity\Company\CompanyConfig;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CompanyConfigRepository extends AbstractRepository
{
    protected string $entityClass = CompanyConfig::class;
}