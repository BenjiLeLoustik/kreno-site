<?php

namespace App\Billing\Service;

use App\Billing\Model\PaymentAttempt;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyInvoice;
use App\Entity\Company\CompanyPaymentMethod;
use App\Entity\User\User;

class InvoicePaymentService
{
    public function __construct(
        private PaymentMethodService $methodService,
        private PaymentService $payments,
    ) {
    }

    public function preferMethod(Company $company, ?CompanyPaymentMethod $method, bool $makeDefault): void
    {
        if ($method === null || !$makeDefault) {
            return;
        }

        $this->methodService->setDefault($company, $method);
    }

    public function pay(CompanyInvoice $invoice, ?CompanyPaymentMethod $method, User $user): ?PaymentAttempt
    {
        return $this->payments->collect($invoice, $method, $user, false);
    }
}