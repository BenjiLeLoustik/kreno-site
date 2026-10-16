<?php

namespace App\Billing\Service;

use App\Billing\Dto\AdminSubscriptionInput;
use App\Billing\Dto\GraceInput;
use App\Billing\Enum\BillingPeriod;
use App\Billing\Enum\InvoiceStatus;
use App\Billing\Event\SubscriptionChangedEvent;
use App\Billing\Helper\PeriodHelper;
use App\Billing\Model\PaymentAttempt;
use App\Entity\Company\Company;
use App\Entity\Company\CompanySubscription;
use App\Entity\User\User;
use App\Entity\Website\WebsitePromoCode;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Repository\Company\CompanyInvoiceRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\MoneyHelper;
use App\Website\Enum\PlanBilling;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class AdminBillingService
{
    public const GESTURE_CODES = [
        'free_month' => 'GESTE-MOIS',
        'discount' => 'GESTE-20',
    ];

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
        $invoice = $this->invoices->findUnpaidForCompany($company)[0] ?? throw new BusinessRuleException('Aucune facture à régler.');
        $attempt = $this->payments->collect($invoice);

        $this->logger->log(ActivityType::BILLING, sprintf('Nouvelle tentative de paiement de la facture %s (%s)', $invoice->getNumber(), $company->getName()), $admin, null, $invoice, ['status' => $attempt?->getPayment()->getStatus()->value]);
        $this->orm->flush();

        return $attempt;
    }

    public function grant(Company $company, User $admin, GraceInput $input): CompanySubscription
    {
        $subscription = $this->subscriptions->findCurrent($company) ?? throw new BusinessRuleException('Aucun abonnement en cours.');
        $this->access->grant($subscription, (int) $input->days);

        $this->logger->log(ActivityType::BILLING, sprintf('Délai de paiement de %d jours accordé à %s', $input->days, $company->getName()), $admin, null, $subscription, ['reason' => $input->reason, 'graceUntil' => $subscription->getGraceUntil()?->format(DATE_ATOM)]);
        $this->orm->flush();

        return $subscription;
    }

    public function change(Company $company, User $admin, AdminSubscriptionInput $input): CompanySubscription
    {
        $current = $this->subscriptions->findCurrent($company) ?? throw new BusinessRuleException('Aucun abonnement en cours.');
        $plan = $this->catalog->get((string) $input->plan);
        $period = $plan->getBilling() === PlanBilling::PER_BOOKING ? BillingPeriod::MONTHLY : BillingPeriod::from($input->period);
        $promo = isset(self::GESTURE_CODES[$input->gesture]) ? $this->gesturePromo($input->gesture) : null;
        $now = new DateTimeImmutable();
        $before = sprintf('%s (%s)', $current->getPlan()?->getName(), $current->getPeriod()->label());

        if ($input->application === 'renewal') {
            $subscription = $this->subscriptionService->schedule($current, $admin, $plan, $period, $promo);
        } else {
            $quote = $this->prices->prorata($current, $plan, $period, $promo, $now);
            $subscription = $this->subscriptionService->replace($current, $admin, $plan, $period, $promo, $now, $quote['periodStart'], $quote['periodEnd']);

            if ($plan->getBilling() === PlanBilling::SUBSCRIPTION && $quote['due'] > 0) {
                $invoice = $this->invoiceService->issue($company, [
                    ['label' => sprintf('%s, prorata au %s', $plan->getName(), PeriodHelper::day($quote['periodEnd'])), 'unitPrice' => $quote['charge']],
                    ['label' => sprintf('Crédit %s non utilisé', $current->getPlan()?->getName()), 'unitPrice' => -min($quote['credit'], $quote['charge'])],
                ], $now, $quote['periodEnd'], $subscription);

                if ($invoice->getTotalIncludingTax() > 0) {
                    $this->payments->collect($invoice);
                }
            }

            $this->events->dispatch(new SubscriptionChangedEvent($subscription, $current));
        }

        if ($input->gesture === 'credit') {
            $last = $this->invoices->findLatestForCompany($company);

            if ($last === null || $last->getStatus() === InvoiceStatus::VOID) {
                throw new BusinessRuleException('Aucune facture sur laquelle émettre un avoir.');
            }

            $this->invoiceService->credit($last, null, sprintf('Avoir sur la facture %s : %s', $last->getNumber(), $input->reason));
        }

        $this->logger->log(ActivityType::BILLING, sprintf('Abonnement de %s modifié : %s → %s (%s)', $company->getName(), $before, $plan->getName(), $input->application === 'renewal' ? 'à la prochaine échéance' : 'immédiatement'), $admin, null, $subscription, [
            'reason' => $input->reason,
            'gesture' => $input->gesture,
            'price' => MoneyHelper::format($this->prices->planPrice($plan, $period)),
        ]);
        $this->orm->flush();

        return $subscription;
    }

    private function gesturePromo(string $gesture): WebsitePromoCode
    {
        return $this->promos->find(self::GESTURE_CODES[$gesture]) ?? throw new BusinessRuleException('Créez d\'abord le code {code} dans « Tarifs et abonnements ».', ['code' => self::GESTURE_CODES[$gesture]]);
    }
}