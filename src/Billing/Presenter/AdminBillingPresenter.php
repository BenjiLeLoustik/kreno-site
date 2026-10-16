<?php

namespace App\Billing\Presenter;

use App\Billing\Enum\InvoiceStatus;
use App\Billing\Enum\PaymentStatus;
use App\Billing\Helper\PaymentMethodHelper;
use App\Billing\Helper\PeriodHelper;
use App\Billing\Service\BillingAccessService;
use App\Billing\Service\SubscriptionService;
use App\Entity\Company\Company;
use App\Entity\Company\CompanySubscription;
use App\Repository\Company\CompanyInvoiceRepository;
use App\Repository\Company\CompanyPaymentMethodRepository;
use App\Shared\Helper\MoneyHelper;

class AdminBillingPresenter
{
    public const STATUSES = [
        'paid' => 'Payé',
        'pending' => 'En attente',
        'failed' => 'Refusé',
        'retry' => 'Nouvelle tentative',
        'none' => 'Aucune facture',
    ];

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
        $invoice = $company !== null ? $this->invoices->findLatestForCompany($company) : null;
        $count = $company !== null ? count($this->subscriptionService->billableEstablishments($company)) : 0;
        $kind = 'none';

        if ($invoice !== null) {
            $kind = match ($invoice->getStatus()) {
                InvoiceStatus::PAID => 'paid',
                InvoiceStatus::FAILED => 'failed',
                default => 'pending',
            };

            foreach ($invoice->getPayments() as $payment) {
                if ($kind === 'failed' && $payment->getStatus() === PaymentStatus::FAILED && $payment->getNextRetryAt() !== null) {
                    $kind = 'retry';
                }
            }
        }

        return [
            'companyId' => $company?->getId(),
            'company' => $company?->getName(),
            'estabs' => sprintf('%d établissement%s', $count, $count > 1 ? 's' : ''),
            'plan' => $subscription->getPlan()?->getName(),
            'subscriptionStatus' => $subscription->getStatus()->value,
            'amount' => $invoice !== null ? MoneyHelper::formatExact($invoice->getTotalIncludingTax()) : '—',
            'kind' => $kind,
            'status' => self::STATUSES[$kind],
            'method' => $company !== null ? PaymentMethodHelper::label($this->methods->findDefault($company)) : null,
            'next' => PeriodHelper::shortDay($subscription->getCurrentPeriodEnd()),
            'since' => $subscription->getStartedAt() !== null ? PeriodHelper::shortDay($subscription->getStartedAt()) . ' ' . $subscription->getStartedAt()->format('Y') : null,
            'isFailed' => in_array($kind, ['failed', 'retry'], true),
        ];
    }

    public function rows(array $subscriptions): array
    {
        return array_values(array_map(fn (CompanySubscription $subscription): array => $this->row($subscription), $subscriptions));
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
}