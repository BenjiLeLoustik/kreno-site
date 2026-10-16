<?php

namespace App\Website\Provider;

use App\Report\Service\ReportService;
use App\Website\Enum\CancellationDelay;

class SiteRulesProvider
{
    public function __construct(
        private WebsiteProvider $website,
    ) {
    }

    public function rules(): array
    {
        $rule = $this->website->getRule();

        return [
            'freeCancellationMinutes' => $rule->getFreeCancellationMinutes(),
            'toleratedNoShows' => $rule->getToleratedNoShows(),
            'suspensionDays' => $rule->getSuspensionDays(),
            'minDiscount' => $rule->getMinDiscount(),
            'maxDiscount' => $rule->getMaxDiscount(),
            'reportWindowHours' => ReportService::CUSTOMER_WINDOW_HOURS,
            'cancellationDelay' => CancellationDelay::format($rule->getFreeCancellationMinutes()),
        ];
    }
}