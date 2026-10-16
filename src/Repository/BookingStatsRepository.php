<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class BookingStatsRepository extends AbstractRepository
{
    protected string $entityClass = Booking::class;

    public function returningCustomerIds(CompanyEstablishment $establishment, array $customerIds, DateTimeImmutable $before): array
    {
        if ($customerIds === []) {
            return [];
        }

        $bookings = $this->createQueryBuilder('b')
            ->join('b.slot', 's')
            ->where('s.establishment = :establishment')
            ->andWhere('b.customer IN (:customers)')
            ->andWhere('b.status IN (:statuses)')
            ->andWhere('s.startsAt < :before')
            ->setParameter('establishment', $establishment)
            ->setParameter('customers', array_values($customerIds))
            ->setParameter('statuses', BookingRepository::KEPT_STATUSES)
            ->setParameter('before', $before)
            ->getResult();

        return array_values(array_unique(array_filter(array_map(static fn (Booking $booking): ?int => $booking->getCustomer()?->getId(), $bookings))));
    }
}