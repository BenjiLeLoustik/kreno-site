<?php

namespace App\Repository\Establishment;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentClosure;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class EstablishmentClosureRepository extends AbstractRepository
{
    protected string $entityClass = EstablishmentClosure::class;

    public function findUpcoming(CompanyEstablishment $establishment, DateTimeImmutable $today): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.establishment = :establishment')
            ->andWhere('c.endsOn >= :today')
            ->setParameter('establishment', $establishment)
            ->setParameter('today', $today->setTime(0, 0))
            ->orderBy('c.startsOn', 'ASC')
            ->getResult();
    }

    public function findOneInEstablishment(CompanyEstablishment $establishment, int $id): ?EstablishmentClosure
    {
        return $this->findOneBy(['establishment' => $establishment, 'id' => $id]);
    }

    public function findCovering(CompanyEstablishment $establishment, DateTimeImmutable $day): ?EstablishmentClosure
    {
        return $this->createQueryBuilder('c')
            ->where('c.establishment = :establishment')
            ->andWhere('c.startsOn <= :day')
            ->andWhere('c.endsOn >= :day')
            ->setParameter('establishment', $establishment)
            ->setParameter('day', $day->setTime(0, 0))
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function overlaps(CompanyEstablishment $establishment, DateTimeImmutable $startsOn, DateTimeImmutable $endsOn): bool
    {
        return (int) $this->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.establishment = :establishment')
                ->andWhere('c.startsOn <= :endsOn')
                ->andWhere('c.endsOn >= :startsOn')
                ->setParameter('establishment', $establishment)
                ->setParameter('startsOn', $startsOn)
                ->setParameter('endsOn', $endsOn)
                ->getSingleScalarResult() > 0;
    }
}