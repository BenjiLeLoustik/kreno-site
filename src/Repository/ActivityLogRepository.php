<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use App\Entity\Website\WebsiteConfig;
use NeoPHP\Package\Orm\Contract\AbstractRepository;
use NeoPHP\Package\Orm\Query\QueryBuilder;

class ActivityLogRepository extends AbstractRepository
{
    protected string $entityClass = ActivityLog::class;

    public function findForAdmin(array $types, ?WebsiteConfig $website = null, ?string $search = null, int $limit = 50, int $offset = 0): array
    {
        return $this->filtered($types, $website, $search)
            ->orderBy('l.createdAt', 'DESC')
            ->addOrderBy('l.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getResult();
    }

    public function countForAdmin(array $types, ?WebsiteConfig $website = null, ?string $search = null): int
    {
        return (int) $this->filtered($types, $website, $search)
            ->select('COUNT(l.id)')
            ->getSingleScalarResult();
    }

    private function filtered(array $types, ?WebsiteConfig $website, ?string $search): QueryBuilder
    {
        $builder = $this->createQueryBuilder('l');

        if ($types !== []) {
            $builder->andWhere('l.type IN (:types)')->setParameter('types', $types);
        }

        if ($website !== null) {
            $builder->andWhere('l.website = :website')->setParameter('website', $website);
        }

        if ($search !== null && trim($search) !== '') {
            $builder->andWhere('l.message LIKE :search')->setParameter('search', '%' . trim($search) . '%');
        }

        return $builder;
    }
}