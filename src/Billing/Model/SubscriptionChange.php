<?php

namespace App\Billing\Model;

use App\Entity\Company\CompanyInvoice;
use App\Entity\Company\CompanySubscription;

class SubscriptionChange
{
    public function __construct(
        private CompanySubscription $subscription,
        private bool $scheduled = false,
        private ?CompanyInvoice $invoice = null,
        private ?PaymentAttempt $attempt = null,
    ) {
    }

    public function getSubscription(): CompanySubscription
    {
        return $this->subscription;
    }

    public function isScheduled(): bool
    {
        return $this->scheduled;
    }

    public function getInvoice(): ?CompanyInvoice
    {
        return $this->invoice;
    }

    public function getAttempt(): ?PaymentAttempt
    {
        return $this->attempt;
    }
}