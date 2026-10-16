<?php

namespace App\Repository\Company;

use App\Entity\Category;
use App\Entity\City;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Establishment\Enum\EstablishmentStatus;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CompanyEstablishmentRepository extends AbstractRepository
{
    protected string $entityClass = CompanyEstablishment::class;

    public function countByCity(City $city, bool $onlineOnly = false): int
    {
        $criteria = ['city' => $city];

        if ($onlineOnly) {
            $criteria['status'] = EstablishmentStatus::ONLINE;
        }

        return $this->count($criteria);
    }

    public function countByCategory(Category $category, ?City $city = null, bool $onlineOnly = false): int
    {
        $criteria = ['category' => $category];

        if ($city !== null) {
            $criteria['city'] = $city;
        }

        if ($onlineOnly) {
            $criteria['status'] = EstablishmentStatus::ONLINE;
        }

        return $this->count($criteria);
    }

    public function findOneInCompany(Company $company, int $id): ?CompanyEstablishment
    {
        return $this->findOneBy(['company' => $company, 'id' => $id]);
    }

    public function findOneBySiret(string $siret): ?CompanyEstablishment
    {
        return $this->findOneBy(['siret' => $siret]);
    }

    public function findOnePublic(City $city, string $slug): ?CompanyEstablishment
    {
        return $this->findOneBy(['city' => $city, 'slug' => $slug, 'status' => EstablishmentStatus::ONLINE]);
    }

    public function slugExistsInCity(City $city, string $slug): bool
    {
        return $this->count(['city' => $city, 'slug' => $slug]) > 0;
    }

    public function findByStatus(EstablishmentStatus $status, int $limit = 50): array
    {
        return $this->findBy(['status' => $status], ['updatedAt' => 'ASC'], $limit);
    }

    public function countByStatus(EstablishmentStatus $status): int
    {
        return $this->count(['status' => $status]);
    }

    public function countVerifiedSince(DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.status = :status')
            ->andWhere('e.verifiedAt >= :since')
            ->setParameter('status', EstablishmentStatus::ONLINE)
            ->setParameter('since', $since)
            ->getSingleScalarResult();
    }

    public function findRecentlyModerated(int $limit = 20): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status IN (:statuses)')
            ->setParameter('statuses', [EstablishmentStatus::ONLINE, EstablishmentStatus::REJECTED])
            ->andWhere('e.verifiedAt IS NOT NULL OR e.rejectionReason IS NOT NULL')
            ->orderBy('e.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getResult();
    }

    public function findForAdmin(?Category $category, ?City $city, array $statuses, ?string $search, int $limit = 100, int $offset = 0): array
    {
        $builder = $this->createQueryBuilder('e')
            ->where('e.status IN (:statuses)')
            ->setParameter('statuses', $statuses);

        if ($category !== null) {
            $builder->andWhere('e.category = :category')->setParameter('category', $category);
        }

        if ($city !== null) {
            $builder->andWhere('e.city = :city')->setParameter('city', $city);
        }

        if ($search !== null && trim($search) !== '') {
            $builder->andWhere('e.name LIKE :search OR e.siret LIKE :search')->setParameter('search', '%' . trim($search) . '%');
        }

        return $builder
            ->orderBy('e.name', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getResult();
    }
}