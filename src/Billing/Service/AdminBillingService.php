<?php

namespace App\Billing\Service;

use App\Billing\Dto\AdminSubscriptionInput;
use App\Billing\Dto\GraceInput;
use App\Billing\Enum\BillingGesture;
use App\Billing\Enum\BillingPeriod;
use App\Billing\Enum\ChangeApplication;
use App\Billing\Enum\InvoiceStatus;
use App\Billing\Event\SubscriptionChangedEvent;
use App\Billing\Exception\MissingGesturePromoException;
use App\Billing\Exception\NoCreditableInvoiceException;
use App\Billing\Exception\NoCurrentSubscriptionException;
use App\Billing\Exception\NoUnpaidInvoiceException;
use App\Billing\Helper\PeriodHelper;
use App\Billing\Model\PaymentAttempt;
use App\Entity\Company\Company;
use App\Entity\Company\CompanySubscription;
use App\Entity\User\User;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Repository\Company\CompanyInvoiceRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Shared\Helper\MoneyHelper;
use App\Website\Enum\PlanBilling;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class AdminBillingService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private EventManagerInterface $events,
        private ActivityLogger $logger,
        private CompanySubscriptionRepository $subscriptions,
        private CompanyInvoiceRepository $invoices,
        private SubscriptionService $subscriptionService,
        private PlanCatalog $catalog,
        private PromoCodeService $promos,
        private PriceCalculator $prices,
        private InvoiceService $invoiceService,
        private PaymentService $payments,
        private BillingAccessService $access,
    ) {
    }

    public function retry(Company $company, User $admin): ?PaymentAttempt
    {
        $invoice = $this->invoices->findUnpaidForCompany($company)[0] ?? throw new NoUnpaidInvoiceException();
        $attempt = $this->payments->collect($invoice);
        $message = sprintf(
            'Nouvelle tentative de paiement de la facture %s (%s)',
            $invoice->getNumber(),
            $company->getName(),
        );

        $this->logger->log(ActivityType::BILLING, $message, $admin, null, $invoice, [
            'status' => $attempt?->getPayment()->getStatus()->value,
        ]);
        $this->orm->flush();

        return $attempt;
    }

    public function grant(Company $company, User $admin, GraceInput $input): CompanySubscription
    {
        $subscription = $this->currentSubscription($company);
        $this->access->grant($subscription, (int) $input->days);
        $message = sprintf('Délai de paiement de %d jours accordé à %s', $input->days, $company->getName());

        $this->logger->log(ActivityType::BILLING, $message, $admin, null, $subscription, [
            'reason' => $input->reason,
            'graceUntil' => $subscription->getGraceUntil()?->format(DATE_ATOM),
        ]);
        $this->orm->flush();

        return $subscription;
    }

    public function change(Company $company, User $admin, AdminSubscriptionInput $input): CompanySubscription
    {
        $current = $this->currentSubscription($company);
        $plan = $this->catalog->get((string) $input->plan);
        $period = $this->period($plan, $input->period);
        $application = ChangeApplication::from($input->application);
        $gesture = BillingGesture::from($input->gesture);
        $promo = $this->gesturePromo($gesture);
        $before = sprintf('%s (%s)', $current->getPlan()?->getName(), $current->getPeriod()->label());

        if ($application === ChangeApplication::RENEWAL) {
            $subscription = $this->subscriptionService->schedule($current, $admin, $plan, $period, $promo);
        } else {
            $subscription = $this->replaceNow($company, $admin, $current, $plan, $period, $promo);
        }

        if ($gesture === BillingGesture::CREDIT) {
            $this->creditLastInvoice($company, (string) $input->reason);
        }

        $message = sprintf(
            'Abonnement de %s modifié : %s → %s (%s)',
            $company->getName(),
            $before,
            $plan->getName(),
            $application->logLabel(),
        );

        $this->logger->log(ActivityType::BILLING, $message, $admin, null, $subscription, [
            'reason' => $input->reason,
            'gesture' => $gesture->value,
            'price' => MoneyHelper::format($this->prices->planPrice($plan, $period)),
        ]);
        $this->orm->flush();

        return $subscription;
    }

    private function currentSubscription(Company $company): CompanySubscription
    {
        return $this->subscriptions->findCurrent($company) ?? throw new NoCurrentSubscriptionException();
    }

    private function period(WebsitePlan $plan, string $period): BillingPeriod
    {
        if ($plan->getBilling() === PlanBilling::PER_BOOKING) {
            return BillingPeriod::MONTHLY;
        }

        return BillingPeriod::from($period);
    }

    private function gesturePromo(BillingGesture $gesture): ?WebsitePromoCode
    {
        $code = $gesture->promoCode();

        if ($code === null) {
            return null;
        }

        return $this->promos->find($code) ?? throw new MissingGesturePromoException($code);
    }

    private function replaceNow(
        Company $company,
        User $admin,
        CompanySubscription $current,
        WebsitePlan $plan,
        BillingPeriod $period,
        ?WebsitePromoCode $promo,
    ): CompanySubscription {
        $now = new DateTimeImmutable();
        $quote = $this->prices->prorata($current, $plan, $period, $promo, $now);
        $subscription = $this->subscriptionService->replace(
            $current,
            $admin,
            $plan,
            $period,
            $promo,
            $now,
            $quote['periodStart'],
            $quote['periodEnd'],
        );

        if ($plan->getBilling() === PlanBilling::SUBSCRIPTION && $quote['due'] > 0) {
            $this->chargeProrata($company, $current, $subscription, $plan, $quote, $now);
        }

        $this->events->dispatch(new SubscriptionChangedEvent($subscription, $current));

        return $subscription;
    }

    private function chargeProrata(
        Company $company,
        CompanySubscription $current,
        CompanySubscription $subscription,
        WebsitePlan $plan,
        array $quote,
        DateTimeImmutable $now,
    ): void {
        $lines = [
            [
                'label' => sprintf('%s, prorata au %s', $plan->getName(), PeriodHelper::day($quote['periodEnd'])),
                'unitPrice' => $quote['charge'],
            ],
            [
                'label' => sprintf('Crédit %s non utilisé', $current->getPlan()?->getName()),
                'unitPrice' => -min($quote['credit'], $quote['charge']),
            ],
        ];

        $invoice = $this->invoiceService->issue($company, $lines, $now, $quote['periodEnd'], $subscription);

        if ($invoice->getTotalIncludingTax() > 0) {
            $this->payments->collect($invoice);
        }
    }

    private function creditLastInvoice(Company $company, string $reason): void
    {
        $last = $this->invoices->findLatestForCompany($company);

        if ($last === null || $last->getStatus() === InvoiceStatus::VOID) {
            throw new NoCreditableInvoiceException();
        }

        $label = sprintf('Avoir sur la facture %s : %s', $last->getNumber(), $reason);

        $this->invoiceService->credit($last, null, $label);
    }
}