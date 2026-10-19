<?php

namespace App\Establishment\Provider;

use App\Entity\Company\CompanyEstablishment;
use App\Repository\Company\CompanySubscriptionRepository;

class ProStatsPageProvider
{
    public function __construct(
        private ProStatsProvider $stats,
        private CompanySubscriptionRepository $subscriptions,
    ) {
    }

    public function index(CompanyEstablishment $establishment, int $period): array
    {
        return [
            'stats' => $this->stats->build($establishment, $period, $this->advanced($establishment)),
        ];
    }

    private function advanced(CompanyEstablishment $establishment): bool
    {
        $company = $establishment->getCompany();

        if ($company === null) {
            return false;
        }

        return $this->subscriptions->findCurrent($company)?->getPlan()?->isAdvancedStats() === true;
    }
}