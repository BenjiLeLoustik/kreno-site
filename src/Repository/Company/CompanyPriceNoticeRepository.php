<?php

namespace App\Repository\Company;

use App\Entity\Company\CompanyPriceNotice;
use App\Entity\Website\WebsiteConfig;
use App\Website\Enum\WebsiteStatus;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CompanyPriceNoticeRepository extends AbstractRepository
{
    protected string $entityClass = CompanyPriceNotice::class;

    public function findPendingFromUnpublished(DateTimeImmutable $now, WebsiteConfig $including): array
    {
        return $this->createQueryBuilder('n')
            ->join('n.website', 'w')
            ->where('w.status IN (:statuses) OR n.website = :including')
            ->andWhere('n.effectiveAt > :now')
            ->setParameter('statuses', [WebsiteStatus::DRAFT, WebsiteStatus::SCHEDULED])
            ->setParameter('including', $including)
            ->setParameter('now', $now)
            ->orderBy('n.updatedAt', 'ASC')
            ->addOrderBy('n.id', 'ASC')
            ->getResult();
    }

    public function findStale(DateTimeImmutable $unscheduledBefore, DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('n')
            ->join('n.website', 'w')
            ->where('w.status = :status')
            ->andWhere('w.updatedAt < :before')
            ->andWhere('n.effectiveAt > :now')
            ->setParameter('status', WebsiteStatus::DRAFT)
            ->setParameter('before', $unscheduledBefore)
            ->setParameter('now', $now)
            ->orderBy('n.updatedAt', 'ASC')
            ->addOrderBy('n.id', 'ASC')
            ->getResult();
    }

    public function findPast(DateTimeImmutable $now, int $limit = 500): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.effectiveAt <= :now')
            ->setParameter('now', $now)
            ->setMaxResults($limit)
            ->getResult();
    }
}