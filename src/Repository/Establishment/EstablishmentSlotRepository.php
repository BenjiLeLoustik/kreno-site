<?php

namespace App\Repository\Establishment;

use App\Entity\Category;
use App\Entity\City;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentMember;
use App\Entity\Establishment\EstablishmentService;
use App\Entity\Establishment\EstablishmentSlot;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Enum\SlotStatus;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class EstablishmentSlotRepository extends AbstractRepository
{
    protected string $entityClass = EstablishmentSlot::class;

    public function findOneInEstablishment(CompanyEstablishment $establishment, int $id): ?EstablishmentSlot
    {
        return $this->findOneBy(['establishment' => $establishment, 'id' => $id]);
    }

    public function findForPro(CompanyEstablishment $establishment, array $statuses, DateTimeImmutable $since, int $limit = 100): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.establishment = :establishment')
            ->andWhere('s.status IN (:statuses)')
            ->andWhere('s.startsAt >= :since')
            ->setParameter('establishment', $establishment)
            ->setParameter('statuses', $statuses)
            ->setParameter('since', $since)
            ->orderBy('s.startsAt', 'DESC')
            ->setMaxResults($limit)
            ->getResult();
    }

    public function countForPro(CompanyEstablishment $establishment, array $statuses, DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.establishment = :establishment')
            ->andWhere('s.status IN (:statuses)')
            ->andWhere('s.startsAt >= :since')
            ->setParameter('establishment', $establishment)
            ->setParameter('statuses', $statuses)
            ->setParameter('since', $since)
            ->getSingleScalarResult();
    }

    public function findBetween(CompanyEstablishment $establishment, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.establishment = :establishment')
            ->andWhere('s.status != :withdrawn')
            ->andWhere('s.startsAt >= :from')
            ->andWhere('s.startsAt < :to')
            ->setParameter('establishment', $establishment)
            ->setParameter('withdrawn', SlotStatus::WITHDRAWN)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('s.startsAt', 'ASC')
            ->getResult();
    }

    public function findDuplicate(CompanyEstablishment $establishment, EstablishmentService $service, DateTimeImmutable $startsAt): ?EstablishmentSlot
    {
        return $this->createQueryBuilder('s')
            ->where('s.establishment = :establishment')
            ->andWhere('s.service = :service')
            ->andWhere('s.startsAt = :startsAt')
            ->andWhere('s.status IN (:statuses)')
            ->setParameter('establishment', $establishment)
            ->setParameter('service', $service)
            ->setParameter('startsAt', $startsAt)
            ->setParameter('statuses', [SlotStatus::AVAILABLE, SlotStatus::BOOKED])
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function hasOverlapForStaff(EstablishmentMember $staff, DateTimeImmutable $startsAt, DateTimeImmutable $endsAt): bool
    {
        return (int) $this->createQueryBuilder('s')
                ->select('COUNT(s.id)')
                ->where('s.staff = :staff')
                ->andWhere('s.status IN (:statuses)')
                ->andWhere('s.startsAt < :endsAt')
                ->andWhere('s.endsAt > :startsAt')
                ->setParameter('staff', $staff)
                ->setParameter('statuses', [SlotStatus::AVAILABLE, SlotStatus::BOOKED])
                ->setParameter('startsAt', $startsAt)
                ->setParameter('endsAt', $endsAt)
                ->getSingleScalarResult() > 0;
    }

    public function findExpirable(DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.visibleUntil <= :now')
            ->setParameter('status', SlotStatus::AVAILABLE)
            ->setParameter('now', $now)
            ->getResult();
    }

    public function findAvailableForEstablishment(CompanyEstablishment $establishment, DateTimeImmutable $now, int $limit = 20): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.establishment = :establishment')
            ->andWhere('s.status = :status')
            ->andWhere('s.visibleUntil > :now')
            ->setParameter('establishment', $establishment)
            ->setParameter('status', SlotStatus::AVAILABLE)
            ->setParameter('now', $now)
            ->orderBy('s.startsAt', 'ASC')
            ->setMaxResults($limit)
            ->getResult();
    }

    public function findAllAvailable(CompanyEstablishment $establishment): array
    {
        return $this->findBy(['establishment' => $establishment, 'status' => SlotStatus::AVAILABLE]);
    }

    public function search(City $city, array $categories, DateTimeImmutable $from, ?DateTimeImmutable $to, DateTimeImmutable $now, int $limit = 200): array
    {
        $builder = $this->createQueryBuilder('s')
            ->join('s.establishment', 'e')
            ->where('e.city = :city')
            ->andWhere('e.status = :online')
            ->andWhere('s.status = :available')
            ->andWhere('s.visibleUntil > :now')
            ->andWhere('s.startsAt >= :from')
            ->setParameter('city', $city)
            ->setParameter('online', EstablishmentStatus::ONLINE)
            ->setParameter('available', SlotStatus::AVAILABLE)
            ->setParameter('now', $now)
            ->setParameter('from', $from);

        if ($to !== null) {
            $builder->andWhere('s.startsAt < :to')->setParameter('to', $to);
        }

        if ($categories !== []) {
            $builder->andWhere('e.category IN (:categories)')->setParameter('categories', array_map(static fn (Category $category): ?int => $category->getId(), $categories));
        }

        return $builder->orderBy('s.startsAt', 'ASC')->setMaxResults($limit)->getResult();
    }

    public function countCreatedSince(CompanyEstablishment $establishment, DateTimeImmutable $since, ?DateTimeImmutable $until = null, ?SlotStatus $status = null): int
    {
        $builder = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.establishment = :establishment')
            ->andWhere('s.createdAt >= :since')
            ->andWhere('s.status != :withdrawn')
            ->setParameter('establishment', $establishment)
            ->setParameter('since', $since)
            ->setParameter('withdrawn', SlotStatus::WITHDRAWN);

        if ($until !== null) {
            $builder->andWhere('s.createdAt < :until')->setParameter('until', $until);
        }

        if ($status !== null) {
            $builder->andWhere('s.status = :status')->setParameter('status', $status);
        }

        return (int) $builder->getSingleScalarResult();
    }

    public function averageDiscountSince(CompanyEstablishment $establishment, DateTimeImmutable $since): ?int
    {
        $average = $this->createQueryBuilder('s')
            ->select('AVG(s.discount)')
            ->where('s.establishment = :establishment')
            ->andWhere('s.createdAt >= :since')
            ->andWhere('s.status != :withdrawn')
            ->setParameter('establishment', $establishment)
            ->setParameter('since', $since)
            ->setParameter('withdrawn', SlotStatus::WITHDRAWN)
            ->getSingleScalarResult();

        return $average !== null ? (int) round((float) $average) : null;
    }
}