<?php

namespace App\Repository\Establishment;

use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentReview;
use App\Entity\User\User;
use App\Review\Enum\ReviewStatus;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class EstablishmentReviewRepository extends AbstractRepository
{
    protected string $entityClass = EstablishmentReview::class;

    public function findOneForBooking(Booking $booking): ?EstablishmentReview
    {
        return $this->findOneBy(['booking' => $booking]);
    }

    public function findForCustomer(User $customer): array
    {
        return $this->findBy(['customer' => $customer], ['createdAt' => 'DESC']);
    }

    public function findVisibleForEstablishment(CompanyEstablishment $establishment, int $limit): array
    {
        $criteria = ['establishment' => $establishment, 'status' => ReviewStatus::visible()];

        return $this->findBy($criteria, ['createdAt' => 'DESC'], $limit);
    }

    public function findForProfessional(CompanyEstablishment $establishment, array $criteria): array
    {
        return $this->findBy(['establishment' => $establishment] + $criteria, ['createdAt' => 'DESC']);
    }

    public function findForAdmin(array $statuses, int $limit): array
    {
        return $this->findBy(['status' => $statuses], ['updatedAt' => 'DESC'], $limit);
    }

    public function countByStatus(ReviewStatus $status): int
    {
        return $this->count(['status' => $status]);
    }

    public function ratingDistribution(CompanyEstablishment $establishment): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.rating AS rating', 'COUNT(r.id) AS total')
            ->where('r.establishment = :establishment')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('establishment', $establishment)
            ->setParameter('statuses', ReviewStatus::visibleValues())
            ->groupBy('r.rating')
            ->getScalarResult();
        $distribution = [];

        foreach ($rows as $row) {
            $distribution[(int) $row['rating']] = (int) $row['total'];
        }

        return $distribution;
    }
}