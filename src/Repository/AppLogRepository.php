<?php

namespace App\Repository;

use App\Entity\AppLog;
use App\Enum\Log\AppLogStatus;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Contract\AbstractRepository;
use NeoPHP\Package\Orm\Query\QueryBuilder;

class AppLogRepository extends AbstractRepository
{
    protected string $entityClass = AppLog::class;

    public function findForAdmin(?AppLogStatus $status, ?string $search, int $limit = 50, int $offset = 0): array
    {
        return $this->filtered($status, $search)
            ->orderBy('l.lastSeenAt', 'DESC')
            ->addOrderBy('l.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getResult();
    }

    public function countByStatus(AppLogStatus $status): int
    {
        return (int) $this->filtered($status, null)
            ->select('COUNT(l.id)')
            ->getSingleScalarResult();
    }

    public function findHandledBefore(DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.status IN (:statuses)')
            ->andWhere('l.lastSeenAt < :date')
            ->setParameter('statuses', [AppLogStatus::RESOLVED, AppLogStatus::IGNORED])
            ->setParameter('date', $date)
            ->getResult();
    }

    private function filtered(?AppLogStatus $status, ?string $search): QueryBuilder
    {
        $builder = $this->createQueryBuilder('l');

        if ($status !== null) {
            $builder->andWhere('l.status = :status')->setParameter('status', $status);
        }

        if ($search !== null && trim($search) !== '') {
            $builder->andWhere('l.message LIKE :search OR l.exceptionClass LIKE :search OR l.url LIKE :search')->setParameter('search', '%' . trim($search) . '%');
        }

        return $builder;
    }
}