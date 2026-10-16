<?php

namespace App\Billing\Presenter;

use App\Billing\Enum\InvoiceStatus;
use App\Billing\Enum\LatestInvoiceStatus;
use App\Billing\Enum\PaymentStatus;
use App\Billing\Helper\PaymentMethodHelper;
use App\Billing\Helper\PeriodHelper;
use App\Billing\Service\BillingAccessService;
use App\Billing\Service\SubscriptionService;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyInvoice;
use App\Entity\Company\CompanySubscription;
use App\Repository\Company\CompanyInvoiceRepository;
use App\Repository\Company\CompanyPaymentMethodRepository;
use App\Shared\Helper\MoneyHelper;

class AdminBillingPresenter
{
    public function __construct(
        private CompanyInvoiceRepository $invoices,
        private CompanyPaymentMethodRepository $methods,
        private SubscriptionService $subscriptionService,
        private BillingAccessService $access,
        private InvoicePresenter $invoicePresenter,
        private SubscriptionPresenter $subscriptionPresenter,
    ) {
    }

    public function row(CompanySubscription $subscription): array
    {
        $company = $subscription->getCompany();

        return [
            ...$this->companyColumns($company),
            ...$this->subscriptionColumns($subscription),
            ...$this->invoiceColumns($company),
        ];
    }

    public function rows(array $subscriptions): array
    {
        return array_values(
            array_map(
                fn (CompanySubscription $subscription): array => $this->row($subscription), $subscriptions
            )
        );
    }

    public function detail(Company $company, CompanySubscription $subscription): array
    {
        return $this->row($subscription) + [
                'subscription' => $this->subscriptionPresenter->summary($subscription),
                'alert' => $this->subscriptionPresenter->alert($company, $subscription),
                'suspended' => $this->access->isSuspended($subscription),
                'invoices' => $this->invoicePresenter->rows($this->invoices->findForCompany($company, 12)),
            ];
    }

    private function companyColumns(?Company $company): array
    {
        if ($company === null) {
            return [
                'companyId' => null,
                'company' => null,
                'estabs' => $this->establishments(0),
                'method' => null
            ];
        }

        return [
            'companyId' => $company->getId(),
            'company' => $company->getName(),
            'estabs' => $this->establishments(count($this->subscriptionService->billableEstablishments($company))),
            'method' => PaymentMethodHelper::label($this->methods->findDefault($company)),
        ];
    }

    private function subscriptionColumns(CompanySubscription $subscription): array
    {
        return [
            'plan' => $subscription->getPlan()?->getName(),
            'subscriptionStatus' => $subscription->getStatus()->value,
            'next' => PeriodHelper::shortDay($subscription->getCurrentPeriodEnd()),
            'since' => PeriodHelper::day($subscription->getStartedAt()),
        ];
    }

    private function invoiceColumns(?Company $company): array
    {
        $invoice = $company !== null ? $this->invoices->findLatestForCompany($company) : null;
        $status = $this->latestStatus($invoice);

        return [
            'amount' => $invoice !== null ? MoneyHelper::formatExact($invoice->getTotalIncludingTax()) : '—',
            'kind' => $status->value,
            'status' => $status->label(),
            'isFailed' => $status->isFailed(),
        ];
    }

    private function establishments(int $count): string
    {
        return sprintf('%d établissement%s', $count, $count > 1 ? 's' : '');
    }

    private function latestStatus(?CompanyInvoice $invoice): LatestInvoiceStatus
    {
        if ($invoice === null) {
            return LatestInvoiceStatus::NONE;
        }

        return match ($invoice->getStatus()) {
            InvoiceStatus::PAID => LatestInvoiceStatus::PAID,
            InvoiceStatus::FAILED => $this->hasPendingRetry($invoice)
                ? LatestInvoiceStatus::RETRY
                : LatestInvoiceStatus::FAILED,
            default => LatestInvoiceStatus::PENDING,
        };
    }

    private function hasPendingRetry(CompanyInvoice $invoice): bool
    {
        foreach ($invoice->getPayments() as $payment) {
            if ($payment->getStatus() === PaymentStatus::FAILED && $payment->getNextRetryAt() !== null) {
                return true;
            }
        }

        return false;
    }
}