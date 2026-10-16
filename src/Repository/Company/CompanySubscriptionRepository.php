<?php

namespace App\Repository\Company;

use App\Billing\Enum\SubscriptionStatus;
use App\Entity\Company\Company;
use App\Entity\Company\CompanySubscription;
use App\Website\Enum\PlanBilling;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CompanySubscriptionRepository extends AbstractRepository
{
    protected string $entityClass = CompanySubscription::class;

    public const CURRENT_STATUSES = [SubscriptionStatus::ACTIVE, SubscriptionStatus::PAST_DUE, SubscriptionStatus::PAUSED];

    public function findCurrent(Company $company): ?CompanySubscription
    {
        return $this->createQueryBuilder('s')
            ->where('s.company = :company')
            ->andWhere('s.status IN (:statuses)')
            ->setParameter('company', $company)
            ->setParameter('statuses', self::CURRENT_STATUSES)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function findPending(Company $company): ?CompanySubscription
    {
        return $this->findOneBy(['company' => $company, 'status' => SubscriptionStatus::PENDING], ['createdAt' => 'DESC']);
    }

    public function findDueForRenewal(DateTimeImmutable $now, int $limit = 200): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status IN (:statuses)')
            ->andWhere('s.currentPeriodEnd <= :now')
            ->setParameter('statuses', self::CURRENT_STATUSES)
            ->setParameter('now', $now)
            ->orderBy('s.currentPeriodEnd', 'ASC')
            ->setMaxResults($limit)
            ->getResult();
    }

    public function findPastDueBeyondGrace(DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.graceUntil IS NOT NULL')
            ->andWhere('s.graceUntil <= :now')
            ->setParameter('status', SubscriptionStatus::PAST_DUE)
            ->setParameter('now', $now)
            ->getResult();
    }

    public function findActiveByBilling(PlanBilling $billing): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.plan', 'p')
            ->where('s.status IN (:statuses)')
            ->andWhere('p.billing = :billing')
            ->setParameter('statuses', self::CURRENT_STATUSES)
            ->setParameter('billing', $billing)
            ->getResult();
    }

    public function findAllCurrent(int $limit = 200, int $offset = 0): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.company', 'c')
            ->where('s.status IN (:statuses)')
            ->setParameter('statuses', [...self::CURRENT_STATUSES, SubscriptionStatus::PENDING])
            ->orderBy('c.name', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getResult();
    }

    public function countStartedBetween(DateTimeImmutable $from, DateTimeImmutable $to, ?PlanBilling $billing = null): int
    {
        $builder = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.plan', 'p')
            ->where('s.startedAt >= :from')
            ->andWhere('s.startedAt < :to')
            ->andWhere('s.previous IS NULL')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        if ($billing !== null) {
            $builder->andWhere('p.billing = :billing')->setParameter('billing', $billing);
        }

        return (int) $builder->getSingleScalarResult();
    }

    public function countCanceledBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.plan', 'p')
            ->where('s.canceledAt >= :from')
            ->andWhere('s.canceledAt < :to')
            ->andWhere('p.billing = :billing')
            ->andWhere('s.cancellationReason IS NULL OR s.cancellationReason != :changed')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('billing', PlanBilling::SUBSCRIPTION)
            ->setParameter('changed', 'changed')
            ->getSingleScalarResult();
    }
}