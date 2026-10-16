<?php

namespace App\Repository\Establishment;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentService;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class EstablishmentServiceRepository extends AbstractRepository
{
    protected string $entityClass = EstablishmentService::class;

    public function findForEstablishment(CompanyEstablishment $establishment): array
    {
        return $this->findBy(['establishment' => $establishment], ['position' => 'ASC', 'id' => 'ASC']);
    }

    public function findActive(CompanyEstablishment $establishment): array
    {
        return $this->findBy(['establishment' => $establishment, 'active' => true], ['position' => 'ASC', 'id' => 'ASC']);
    }

    public function findOneInEstablishment(CompanyEstablishment $establishment, int $id): ?EstablishmentService
    {
        return $this->findOneBy(['establishment' => $establishment, 'id' => $id]);
    }

    public function nextPosition(CompanyEstablishment $establishment): int
    {
        return $this->count(['establishment' => $establishment]);
    }
}