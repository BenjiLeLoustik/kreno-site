<?php

namespace App\Repository\Report;

use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Report\Report;
use App\Entity\User\User;
use App\Report\Enum\ReportDirection;
use App\Report\Enum\ReportStatus;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class ReportRepository extends AbstractRepository
{
    protected string $entityClass = Report::class;

    public const OPEN_STATUSES = [ReportStatus::SENT, ReportStatus::IN_REVIEW, ReportStatus::AWAITING_REPLY];

    public const CLOSED_STATUSES = [ReportStatus::RESOLVED, ReportStatus::DISMISSED];

    public function findForReporter(User $reporter, int $limit = 50): array
    {
        return $this->findBy(['reporter' => $reporter, 'direction' => ReportDirection::FROM_CUSTOMER], ['createdAt' => 'DESC'], $limit);
    }

    public function findOneForReporter(User $reporter, int $id): ?Report
    {
        return $this->findOneBy(['reporter' => $reporter, 'id' => $id, 'direction' => ReportDirection::FROM_CUSTOMER]);
    }

    public function findByEstablishment(CompanyEstablishment $establishment, ReportDirection $direction, int $limit = 50): array
    {
        return $this->findBy(['establishment' => $establishment, 'direction' => $direction], ['createdAt' => 'DESC'], $limit);
    }

    public function findOneForEstablishment(CompanyEstablishment $establishment, int $id): ?Report
    {
        return $this->findOneBy(['establishment' => $establishment, 'id' => $id]);
    }

    public function existsForBooking(Booking $booking, ReportDirection $direction): bool
    {
        return $this->count(['booking' => $booking, 'direction' => $direction]) > 0;
    }

    public function countAboutEstablishmentSince(CompanyEstablishment $establishment, DateTimeImmutable $since, ?Report $exclude = null): int
    {
        $builder = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.establishment = :establishment')
            ->andWhere('r.direction = :direction')
            ->andWhere('r.createdAt >= :since')
            ->setParameter('establishment', $establishment)
            ->setParameter('direction', ReportDirection::FROM_CUSTOMER)
            ->setParameter('since', $since);

        if ($exclude !== null) {
            $builder->andWhere('r.id != :exclude')->setParameter('exclude', $exclude->getId());
        }

        return (int) $builder->getSingleScalarResult();
    }

    public function countAboutUserSince(User $user, DateTimeImmutable $since, ?Report $exclude = null): int
    {
        $builder = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.reportedUser = :user')
            ->andWhere('r.createdAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since);

        if ($exclude !== null) {
            $builder->andWhere('r.id != :exclude')->setParameter('exclude', $exclude->getId());
        }

        return (int) $builder->getSingleScalarResult();
    }

    public function countOpenAboutEstablishment(CompanyEstablishment $establishment): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.establishment = :establishment')
            ->andWhere('r.direction = :direction')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('establishment', $establishment)
            ->setParameter('direction', ReportDirection::FROM_CUSTOMER)
            ->setParameter('statuses', self::OPEN_STATUSES)
            ->getSingleScalarResult();
    }

    public function countOpen(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status IN (:statuses)')
            ->setParameter('statuses', self::OPEN_STATUSES)
            ->getSingleScalarResult();
    }

    public function findForAdmin(array $statuses, ?ReportDirection $direction = null, int $limit = 100, int $offset = 0): array
    {
        $builder = $this->createQueryBuilder('r')
            ->where('r.status IN (:statuses)')
            ->setParameter('statuses', $statuses);

        if ($direction !== null) {
            $builder->andWhere('r.direction = :direction')->setParameter('direction', $direction);
        }

        return $builder
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getResult();
    }

    public function findAwaitingReplyBefore(DateTimeImmutable $before): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->andWhere('r.createdAt <= :before')
            ->setParameter('status', ReportStatus::AWAITING_REPLY)
            ->setParameter('before', $before)
            ->getResult();
    }
}