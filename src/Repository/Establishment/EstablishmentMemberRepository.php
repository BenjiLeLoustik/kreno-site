<?php

namespace App\Repository\Establishment;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\Establishment\EstablishmentMember;
use App\Establishment\Enum\EstablishmentRole;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class EstablishmentMemberRepository extends AbstractRepository
{
    protected string $entityClass = EstablishmentMember::class;

    public function findOneByEstablishmentAndMember(CompanyEstablishment $establishment, CompanyMember $member): ?EstablishmentMember
    {
        return $this->findOneBy(['establishment' => $establishment, 'member' => $member]);
    }

    public function findOneInEstablishment(CompanyEstablishment $establishment, int $id): ?EstablishmentMember
    {
        return $this->findOneBy(['establishment' => $establishment, 'id' => $id]);
    }

    public function findForEstablishment(CompanyEstablishment $establishment): array
    {
        return $this->findBy(['establishment' => $establishment], ['createdAt' => 'ASC']);
    }

    public function findManager(CompanyEstablishment $establishment): ?EstablishmentMember
    {
        return $this->findOneBy(['establishment' => $establishment, 'role' => EstablishmentRole::MANAGER], ['createdAt' => 'ASC']);
    }
}