<?php

namespace App\Repository\Establishment;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentOpeningHour;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class EstablishmentOpeningHourRepository extends AbstractRepository
{
    protected string $entityClass = EstablishmentOpeningHour::class;

    public function findForEstablishment(CompanyEstablishment $establishment): array
    {
        return $this->findBy(['establishment' => $establishment], ['weekday' => 'ASC', 'opensAt' => 'ASC']);
    }
}