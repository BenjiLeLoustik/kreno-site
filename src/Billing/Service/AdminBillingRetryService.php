<?php

namespace App\Billing\Service;

use App\Billing\Enum\PaymentStatus;
use App\Entity\Company\Company;
use App\Entity\User\User;

class AdminBillingRetryService
{
    public function __construct(
        private AdminBillingService $billing,
    ) {
    }

    public function retry(Company $company, User $admin): PaymentStatus
    {
        return $this->billing->retry($company, $admin)?->getPayment()->getStatus() ?? PaymentStatus::SUCCEEDED;
    }
}