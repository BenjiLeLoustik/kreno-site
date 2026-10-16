<?php

namespace App\Repository\Establishment;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentImage;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class EstablishmentImageRepository extends AbstractRepository
{
    protected string $entityClass = EstablishmentImage::class;

    public function findForEstablishment(CompanyEstablishment $establishment): array
    {
        return $this->findBy(['establishment' => $establishment], ['position' => 'ASC', 'id' => 'ASC']);
    }

    public function findOneInEstablishment(CompanyEstablishment $establishment, int $id): ?EstablishmentImage
    {
        return $this->findOneBy(['establishment' => $establishment, 'id' => $id]);
    }

    public function findCover(CompanyEstablishment $establishment): ?EstablishmentImage
    {
        return $this->findOneBy(['establishment' => $establishment], ['position' => 'ASC', 'id' => 'ASC']);
    }

    public function countForEstablishment(CompanyEstablishment $establishment): int
    {
        return $this->count(['establishment' => $establishment]);
    }
}