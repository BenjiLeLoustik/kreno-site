<?php

namespace App\Repository\Establishment;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentFollower;
use App\Entity\User\User;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class EstablishmentFollowerRepository extends AbstractRepository
{
    protected string $entityClass = EstablishmentFollower::class;

    public function findOneFor(CompanyEstablishment $establishment, User $user): ?EstablishmentFollower
    {
        return $this->findOneBy(['establishment' => $establishment, 'user' => $user]);
    }

    public function isFollowing(CompanyEstablishment $establishment, User $user): bool
    {
        return $this->count(['establishment' => $establishment, 'user' => $user]) > 0;
    }

    public function countForEstablishment(CompanyEstablishment $establishment): int
    {
        return $this->count(['establishment' => $establishment]);
    }

    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }

    public function findByEstablishment(CompanyEstablishment $establishment): array
    {
        return $this->findBy(['establishment' => $establishment], ['createdAt' => 'ASC']);
    }
}