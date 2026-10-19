<?php

declare(strict_types=1);

namespace App\Repository;

use App\Catalog\Enum\CityStatus;
use App\Entity\City;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CityRepository extends AbstractRepository
{
    protected string $entityClass = City::class;

    public function findOpen(): array
    {
        return $this->findBy(['status' => CityStatus::OPEN], ['position' => 'ASC', 'name' => 'ASC']);
    }

    public function findAvailableForPros(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.status IN (:statuses)')
            ->setParameter('statuses', [CityStatus::OPEN, CityStatus::WAITLIST])
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getResult();
    }

    public function findOneBySlug(string $slug): ?City
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findOneOpenBySlug(string $slug): ?City
    {
        return $this->findOneBy(['slug' => $slug, 'status' => CityStatus::OPEN]);
    }

    public function findAllForAdmin(): array
    {
        return $this->findAll(['position' => 'ASC', 'name' => 'ASC']);
    }

    public function slugExists(string $slug): bool
    {
        return $this->count(['slug' => $slug]) > 0;
    }

    public function nextPosition(): int
    {
        return $this->count();
    }
}