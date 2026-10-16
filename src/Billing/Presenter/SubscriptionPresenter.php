<?php

namespace App\Billing\Presenter;

use App\Billing\Enum\BillingPeriod;
use App\Billing\Enum\CancelReason;
use App\Billing\Enum\PaymentStatus;
use App\Billing\Enum\SubscriptionStatus;
use App\Billing\Helper\PaymentMethodHelper;
use App\Billing\Helper\PeriodHelper;
use App\Billing\Service\BillingAccessService;
use App\Billing\Service\PlanCatalog;
use App\Billing\Service\PriceCalculator;
use App\Billing\Service\PromoCodeService;
use App\Billing\Service\RenewalService;
use App\Billing\Service\SubscriptionService;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyInvoice;
use App\Entity\Company\CompanyPayment;
use App\Entity\Company\CompanySubscription;
use App\Entity\Website\WebsitePlan;
use App\Repository\Company\CompanyInvoiceRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Shared\Helper\MoneyHelper;
use App\Website\Enum\PlanBilling;
use DateTimeImmutable;

class SubscriptionPresenter
{
    public function __construct(
        private CompanySubscriptionRepository $subscriptions,
        private CompanyInvoiceRepository $invoices,
        private SubscriptionService $subscriptionService,
        private RenewalService $renewals,
        private PlanCatalog $catalog,
        private PriceCalculator $prices,
        private PromoCodeService $promos,
        private BillingAccessService $access,
    ) {
    }

    public function summary(CompanySubscription $subscription, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $plan = $subscription->getPlan();
        $company = $subscription->getCompany();
        $end = $subscription->getCurrentPeriodEnd();
        $perBooking = $this->isPerBooking($subscription);
        $successor = $this->successor($subscription);

        return [
            'id' => $subscription->getId(),
            'status' => $subscription->getStatus()->value,
            'statusLabel' => $subscription->getStatus()->label(),
            'plan' => ['code' => $plan?->getCode(), 'name' => $plan?->getName(), 'perBooking' => $perBooking],
            'period' => $subscription->getPeriod()->value,
            'periodLabel' => $subscription->getPeriod()->label(),
            'priceLine' => $this->priceLine($subscription),
            'nextDate' => PeriodHelper::shortDay($end),
            'commitment' => $this->commitment($plan),
            'cancelAt' => PeriodHelper::shortDay($subscription->getCancelAt()),
            'pausedUntil' => PeriodHelper::shortDay($subscription->getPausedUntil()),
            'promo' => $this->promo($subscription, $now),
            'nextInvoice' => $this->nextInvoice($subscription, $successor),
            'scheduled' => $this->scheduled($successor, $end),
            'establishments' => $this->establishments($subscription),
            'canCancel' => !$perBooking && $subscription->getCancelAt() === null && $successor === null,
            'canRevoke' => $subscription->getCancelAt() !== null,
            'blockingReason' => $this->blockingReason($company, $now),
        ];
    }

    public function alert(Company $company, ?CompanySubscription $subscription): ?array
    {
        $unpaid = $this->invoices->findUnpaidForCompany($company);

        if ($unpaid === []) {
            return null;
        }

        $invoice = $unpaid[0];
        $failed = $this->failedPayment($invoice);

        if ($failed === null) {
            return null;
        }

        return [
            'invoice' => $invoice->getNumber(),
            'amount' => MoneyHelper::formatExact($invoice->getTotalExcludingTax()) . ' HT',
            'amountIncludingTax' => MoneyHelper::formatExact($invoice->getTotalIncludingTax()),
            'failedAt' => PeriodHelper::shortDay($failed->getAttemptedAt()),
            'method' => PaymentMethodHelper::label($failed->getPaymentMethod()),
            'reason' => $this->failureReason($failed),
            'nextRetry' => PeriodHelper::shortDay($failed->getNextRetryAt()),
            'graceUntil' => PeriodHelper::shortDay($subscription?->getGraceUntil()),
            'suspended' => $subscription !== null && $this->access->isSuspended($subscription),
        ];
    }

    public function cancellation(CompanySubscription $subscription): array
    {
        $end = $subscription->getCurrentPeriodEnd();
        $plan = $subscription->getPlan();
        $perBooking = $this->catalog->defaultPerBooking();
        $fee = MoneyHelper::formatExact($perBooking->getPrice());
        $count = count($this->billableEstablishments($subscription));
        $switchDate = PeriodHelper::shortDay($end);
        $acknowledgement = 'Je comprends que mes créneaux seront facturés %s HT par réservation à partir du %s.';

        return [
            'plan' => $plan?->getName(),
            'company' => $subscription->getCompany()?->getName(),
            'activeUntil' => $this->activeUntil($end),
            'switchDate' => $switchDate,
            'nextPlan' => $perBooking->getName(),
            'refund' => 'Aucun, période en cours due',
            'establishmentsCount' => $count,
            'keep' => $this->kept($count),
            'lose' => $this->lost($plan, $fee),
            'reasons' => $this->reasons(),
            'offers' => $this->offers($subscription, $perBooking, $fee),
            'acknowledgement' => sprintf($acknowledgement, $fee, $switchDate),
        ];
    }

    private function isPerBooking(CompanySubscription $subscription): bool
    {
        return $subscription->getPlan()?->getBilling() === PlanBilling::PER_BOOKING;
    }

    private function vatRate(CompanySubscription $subscription): int
    {
        return $subscription->getPlan()?->getVatRate() ?? 20;
    }

    private function successor(CompanySubscription $subscription): ?CompanySubscription
    {
        $company = $subscription->getCompany();

        if ($company === null) {
            return null;
        }

        $pending = $this->subscriptions->findPending($company);

        if ($pending === null || $pending->getPrevious() !== $subscription) {
            return null;
        }

        return $pending;
    }

    private function billableEstablishments(CompanySubscription $subscription): array
    {
        $company = $subscription->getCompany();

        if ($company === null) {
            return [];
        }

        return $this->subscriptionService->billableEstablishments($company);
    }

    private function priceLine(CompanySubscription $subscription): string
    {
        $unit = $subscription->getUnitPrice();
        $price = MoneyHelper::formatExact($unit);

        if ($this->isPerBooking($subscription)) {
            return sprintf('%s HT par créneau réservé', $price);
        }

        $priceIncludingTax = MoneyHelper::formatExact(MoneyHelper::includingTax($unit, $this->vatRate($subscription)));

        if ($subscription->getPeriod() === BillingPeriod::YEARLY) {
            return sprintf('%s HT / an · %s TTC', $price, $priceIncludingTax);
        }

        return sprintf('%s HT / mois · %s TTC', $price, $priceIncludingTax);
    }

    private function commitment(?WebsitePlan $plan): string
    {
        $months = $plan?->getCommitmentMonths() ?? 0;

        if ($months > 0) {
            return sprintf('engagement %d mois', $months);
        }

        return 'sans engagement';
    }

    private function promo(CompanySubscription $subscription, DateTimeImmutable $now): ?string
    {
        $promo = $subscription->getPromoCode();
        $endsAt = $subscription->getPromoEndsAt();

        if ($promo === null || $endsAt === null || $endsAt <= $now) {
            return null;
        }

        $description = $this->promos->describe($promo);

        return sprintf(
            'Code %s : %s, jusqu\'au %s',
            $promo->getCode(),
            $description,
            PeriodHelper::shortDay($endsAt)
        );
    }

    private function nextInvoice(CompanySubscription $subscription, ?CompanySubscription $successor): ?array
    {
        $end = $subscription->getCurrentPeriodEnd();

        if ($end === null || $successor !== null || $this->isPerBooking($subscription)) {
            return null;
        }

        if ($subscription->getStatus() === SubscriptionStatus::PAUSED) {
            return null;
        }

        $amount = $this->prices->amountFor($subscription, $end);
        $amountIncludingTax = MoneyHelper::includingTax($amount, $this->vatRate($subscription));

        return [
            'date' => PeriodHelper::shortDay($end),
            'amount' => MoneyHelper::formatExact($amountIncludingTax) . ' TTC',
            'label' => sprintf('%s, %s', $subscription->getPlan()?->getName(), PeriodHelper::monthName($end)),
        ];
    }

    private function scheduled(?CompanySubscription $successor, ?DateTimeImmutable $end): ?array
    {
        if ($successor === null) {
            return null;
        }

        return [
            'plan' => $successor->getPlan()?->getName(),
            'date' => PeriodHelper::shortDay($end)
        ];
    }

    private function establishments(CompanySubscription $subscription): array
    {
        $establishments = $this->billableEstablishments($subscription);
        $total = count($establishments);

        if ($this->isPerBooking($subscription)) {
            return [
                'total' => $total,
                'covered' => 0,
                'uncovered' => 0
            ];
        }

        $covered = 0;

        foreach (array_keys($establishments) as $index) {
            if ($this->subscriptionService->covers($subscription, $index)) {
                ++$covered;
            }
        }

        return [
            'total' => $total,
            'covered' => $covered,
            'uncovered' => $total - $covered
        ];
    }

    private function blockingReason(?Company $company, DateTimeImmutable $now): ?string
    {
        if ($company === null) {
            return null;
        }

        return $this->access->blockingReason($company, $now);
    }

    private function failedPayment(CompanyInvoice $invoice): ?CompanyPayment
    {
        $failed = null;

        foreach ($invoice->getPayments() as $payment) {
            if (!$payment instanceof CompanyPayment) {
                continue;
            }

            if (in_array($payment->getStatus(), [PaymentStatus::FAILED, PaymentStatus::REQUIRES_ACTION], true)) {
                $failed = $payment;
            }
        }

        return $failed;
    }

    private function failureReason(CompanyPayment $payment): ?string
    {
        if ($payment->getStatus() === PaymentStatus::REQUIRES_ACTION) {
            return 'validation bancaire requise';
        }

        return $payment->getFailureMessage();
    }

    private function activeUntil(?DateTimeImmutable $end): ?string
    {
        if ($end === null) {
            return null;
        }

        return PeriodHelper::day($end->modify('-1 day'));
    }

    private function kept(int $count): array
    {
        $pages = sprintf('Vos %d page établissement', $count);

        if ($count > 1) {
            $pages = sprintf('Vos %d pages établissement', $count);
        }

        return [
            $pages,
            'Vos créneaux, réservations et avis',
            'Votre historique et vos factures'
        ];
    }

    private function lost(?WebsitePlan $plan, string $fee): array
    {
        $lost = [sprintf('Commission de %s HT par créneau réservé', $fee)];

        if ($plan?->isFeatured()) {
            $lost[] = 'Plus de mise en avant dans le quartier';
        }

        if ($plan?->isAdvancedStats()) {
            $lost[] = 'Statistiques détaillées masquées';
        }

        return $lost;
    }

    private function reasons(): array
    {
        $reasons = [];

        foreach (CancelReason::cases() as $reason) {
            $reasons[] = ['key' => $reason->value, 'label' => $reason->label()];
        }

        return $reasons;
    }

    private function offers(CompanySubscription $subscription, WebsitePlan $perBooking, string $fee): array
    {
        $offers = [];
        $retention = $this->retentionOffer($subscription);

        if ($retention !== null) {
            $offers[CancelReason::PRICE->value] = $retention;
        }

        $offers[CancelReason::VOLUME->value] = [
            'title' => sprintf('Passez plutôt « %s »', $perBooking->getName()),
            'text' => sprintf('Vous paierez seulement %s HT par créneau réservé, sans résilier.', $fee),
            'cta' => 'Changer de formule',
        ];

        $offers[CancelReason::PAUSE->value] = [
            'title' => sprintf('Mettre en pause %d mois', RenewalService::PAUSE_MONTHS),
            'text' => 'Fermeture, congés, travaux : suspendez l’abonnement sans perdre votre page.',
            'cta' => 'Mettre en pause',
        ];

        return $offers;
    }

    private function retentionOffer(CompanySubscription $subscription): ?array
    {
        if (!$this->renewals->retentionPromoAvailable($subscription)) {
            return null;
        }

        $promo = $this->promos->find(RenewalService::RETENTION_PROMO_CODE);

        if ($promo === null) {
            return null;
        }

        $text = 'Restez sur %s avec cette remise appliquée dès la prochaine facture.';

        return [
            'title' => $this->promos->describe($promo),
            'text' => sprintf($text, $subscription->getPlan()?->getName()),
            'cta' => 'Profiter de l’offre',
        ];
    }
}