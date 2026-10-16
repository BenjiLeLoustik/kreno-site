<?php

namespace App\Repository;

use App\Booking\Enum\BookingStatus;
use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Contract\AbstractRepository;
use NeoPHP\Package\Orm\Query\QueryBuilder;

class BookingRepository extends AbstractRepository
{
    protected string $entityClass = Booking::class;

    public const KEPT_STATUSES = [BookingStatus::CONFIRMED, BookingStatus::HONORED, BookingStatus::NO_SHOW];

    public const CANCELLED_STATUSES = [BookingStatus::CANCELLED_BY_CUSTOMER, BookingStatus::CANCELLED_BY_ESTABLISHMENT];

    public function countByCustomer(User $customer, ?BookingStatus $status = null): int
    {
        $criteria = ['customer' => $customer];

        if ($status !== null) {
            $criteria['status'] = $status;
        }

        return $this->count($criteria);
    }

    public function countNoShowsSince(User $customer, DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.customer = :customer')
            ->andWhere('b.status = :status')
            ->andWhere('b.noShowAt >= :since')
            ->setParameter('customer', $customer)
            ->setParameter('status', BookingStatus::NO_SHOW)
            ->setParameter('since', $since)
            ->getSingleScalarResult();
    }

    public function findUpcomingForCustomer(User $customer, DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.slot', 's')
            ->where('b.customer = :customer')
            ->andWhere('b.status = :status')
            ->andWhere('s.startsAt > :now')
            ->setParameter('customer', $customer)
            ->setParameter('status', BookingStatus::CONFIRMED)
            ->setParameter('now', $now)
            ->orderBy('s.startsAt', 'ASC')
            ->getResult();
    }

    public function countUpcomingForCustomer(User $customer, DateTimeImmutable $now): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->join('b.slot', 's')
            ->where('b.customer = :customer')
            ->andWhere('b.status = :status')
            ->andWhere('s.startsAt > :now')
            ->setParameter('customer', $customer)
            ->setParameter('status', BookingStatus::CONFIRMED)
            ->setParameter('now', $now)
            ->getSingleScalarResult();
    }

    public function findOneByReference(string $reference): ?Booking
    {
        return $this->findOneBy(['reference' => mb_strtoupper($reference)]);
    }

    public function referenceExists(string $reference): bool
    {
        return $this->count(['reference' => $reference]) > 0;
    }

    public function findForCustomer(User $customer, array $statuses, ?DateTimeImmutable $after = null, ?DateTimeImmutable $before = null, int $limit = 50): array
    {
        $builder = $this->createQueryBuilder('b')
            ->join('b.slot', 's')
            ->where('b.customer = :customer')
            ->andWhere('b.status IN (:statuses)')
            ->setParameter('customer', $customer)
            ->setParameter('statuses', $statuses);

        if ($after !== null) {
            $builder->andWhere('s.startsAt > :after')->setParameter('after', $after);
        }

        if ($before !== null) {
            $builder->andWhere('s.startsAt <= :before')->setParameter('before', $before);
        }

        return $builder
            ->orderBy('s.startsAt', $after !== null ? 'ASC' : 'DESC')
            ->setMaxResults($limit)
            ->getResult();
    }

    public function countKeptForCustomer(User $customer): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.customer = :customer')
            ->andWhere('b.status IN (:statuses)')
            ->setParameter('customer', $customer)
            ->setParameter('statuses', self::KEPT_STATUSES)
            ->getSingleScalarResult();
    }

    public function findForEstablishment(CompanyEstablishment $establishment, array $statuses, ?DateTimeImmutable $after = null, ?DateTimeImmutable $before = null, int $limit = 100): array
    {
        return $this->establishmentQuery($establishment, $statuses, $after, $before)
            ->orderBy('s.startsAt', $after !== null ? 'ASC' : 'DESC')
            ->setMaxResults($limit)
            ->getResult();
    }

    public function countForEstablishment(CompanyEstablishment $establishment, array $statuses, ?DateTimeImmutable $after = null, ?DateTimeImmutable $before = null): int
    {
        return (int) $this->establishmentQuery($establishment, $statuses, $after, $before)
            ->select('COUNT(b.id)')
            ->getSingleScalarResult();
    }

    public function findOneInEstablishment(CompanyEstablishment $establishment, int $id): ?Booking
    {
        return $this->createQueryBuilder('b')
            ->join('b.slot', 's')
            ->where('b.id = :id')
            ->andWhere('s.establishment = :establishment')
            ->setParameter('id', $id)
            ->setParameter('establishment', $establishment)
            ->getOneOrNullResult();
    }

    public function countForCustomerAtEstablishment(User $customer, CompanyEstablishment $establishment, ?BookingStatus $status = null): int
    {
        $builder = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->join('b.slot', 's')
            ->where('b.customer = :customer')
            ->andWhere('s.establishment = :establishment')
            ->setParameter('customer', $customer)
            ->setParameter('establishment', $establishment);

        if ($status !== null) {
            $builder->andWhere('b.status = :status')->setParameter('status', $status);
        } else {
            $builder->andWhere('b.status IN (:statuses)')->setParameter('statuses', self::KEPT_STATUSES);
        }

        return (int) $builder->getSingleScalarResult();
    }

    public function findRecentForEstablishment(CompanyEstablishment $establishment, int $limit = 5): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.slot', 's')
            ->where('s.establishment = :establishment')
            ->andWhere('b.status IN (:statuses)')
            ->setParameter('establishment', $establishment)
            ->setParameter('statuses', self::KEPT_STATUSES)
            ->orderBy('b.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getResult();
    }

    public function findForEstablishmentSince(CompanyEstablishment $establishment, DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.slot', 's')
            ->where('s.establishment = :establishment')
            ->andWhere('b.status IN (:statuses)')
            ->andWhere('b.createdAt >= :since')
            ->setParameter('establishment', $establishment)
            ->setParameter('statuses', self::KEPT_STATUSES)
            ->setParameter('since', $since)
            ->getResult();
    }

    public function sumPriceForEstablishmentSince(CompanyEstablishment $establishment, DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('SUM(b.price)')
            ->join('b.slot', 's')
            ->where('s.establishment = :establishment')
            ->andWhere('b.status IN (:statuses)')
            ->andWhere('b.createdAt >= :since')
            ->setParameter('establishment', $establishment)
            ->setParameter('statuses', [BookingStatus::CONFIRMED, BookingStatus::HONORED])
            ->setParameter('since', $since)
            ->getSingleScalarResult();
    }

    public function customerBookedBefore(CompanyEstablishment $establishment, User $customer, DateTimeImmutable $before): bool
    {
        return (int) $this->createQueryBuilder('b')
                ->select('COUNT(b.id)')
                ->join('b.slot', 's')
                ->where('s.establishment = :establishment')
                ->andWhere('b.customer = :customer')
                ->andWhere('b.createdAt < :before')
                ->setParameter('establishment', $establishment)
                ->setParameter('customer', $customer)
                ->setParameter('before', $before)
                ->getSingleScalarResult() > 0;
    }

    public function findKeptSince(DateTimeImmutable $since, int $limit = 500): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.status IN (:statuses)')
            ->andWhere('b.createdAt >= :since')
            ->setParameter('statuses', self::KEPT_STATUSES)
            ->setParameter('since', $since)
            ->orderBy('b.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getResult();
    }

    public function findStartingBetween(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.slot', 's')
            ->where('b.status = :status')
            ->andWhere('s.startsAt > :from')
            ->andWhere('s.startsAt <= :to')
            ->setParameter('status', BookingStatus::CONFIRMED)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getResult();
    }

    public function findConfirmedEndedBefore(DateTimeImmutable $before, int $limit = 500): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.slot', 's')
            ->where('b.status = :status')
            ->andWhere('s.endsAt <= :before')
            ->setParameter('status', BookingStatus::CONFIRMED)
            ->setParameter('before', $before)
            ->setMaxResults($limit)
            ->getResult();
    }

    public function findForAdmin(array $statuses, ?string $search, int $limit = 100, int $offset = 0): array
    {
        $builder = $this->createQueryBuilder('b')
            ->join('b.slot', 's')
            ->join('s.establishment', 'e')
            ->join('b.customer', 'c')
            ->where('b.status IN (:statuses)')
            ->setParameter('statuses', $statuses);

        if ($search !== null && trim($search) !== '') {
            $builder
                ->andWhere('b.reference LIKE :search OR e.name LIKE :search OR c.email LIKE :search OR c.lastName LIKE :search')
                ->setParameter('search', '%' . trim($search) . '%');
        }

        return $builder
            ->orderBy('s.startsAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getResult();
    }

    public function countCreatedBetween(DateTimeImmutable $from, DateTimeImmutable $to, ?array $statuses = null): int
    {
        $builder = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.createdAt >= :from')
            ->andWhere('b.createdAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        if ($statuses !== null) {
            $builder->andWhere('b.status IN (:statuses)')->setParameter('statuses', $statuses);
        }

        return (int) $builder->getSingleScalarResult();
    }

    public function countCancelledBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.cancelledAt >= :from')
            ->andWhere('b.cancelledAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getSingleScalarResult();
    }

    public function countNoShowsBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.noShowAt >= :from')
            ->andWhere('b.noShowAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getSingleScalarResult();
    }

    public function findBillableBetween(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.slot', 's')
            ->where('b.status IN (:statuses)')
            ->andWhere('b.fee > 0')
            ->andWhere('b.invoiceLine IS NULL')
            ->andWhere('s.startsAt >= :from')
            ->andWhere('s.startsAt < :to')
            ->setParameter('statuses', self::KEPT_STATUSES)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('s.startsAt', 'ASC')
            ->getResult();
    }

    public function sumFeesBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('SUM(b.fee)')
            ->join('b.slot', 's')
            ->where('b.status IN (:statuses)')
            ->andWhere('s.startsAt >= :from')
            ->andWhere('s.startsAt < :to')
            ->setParameter('statuses', self::KEPT_STATUSES)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getSingleScalarResult();
    }

    public function countFeesBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->join('b.slot', 's')
            ->where('b.status IN (:statuses)')
            ->andWhere('b.fee > 0')
            ->andWhere('s.startsAt >= :from')
            ->andWhere('s.startsAt < :to')
            ->setParameter('statuses', self::KEPT_STATUSES)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getSingleScalarResult();
    }

    private function establishmentQuery(CompanyEstablishment $establishment, array $statuses, ?DateTimeImmutable $after, ?DateTimeImmutable $before): QueryBuilder
    {
        $builder = $this->createQueryBuilder('b')
            ->join('b.slot', 's')
            ->where('s.establishment = :establishment')
            ->andWhere('b.status IN (:statuses)')
            ->setParameter('establishment', $establishment)
            ->setParameter('statuses', $statuses);

        if ($after !== null) {
            $builder->andWhere('s.startsAt > :after')->setParameter('after', $after);
        }

        if ($before !== null) {
            $builder->andWhere('s.startsAt <= :before')->setParameter('before', $before);
        }

        return $builder;
    }
}