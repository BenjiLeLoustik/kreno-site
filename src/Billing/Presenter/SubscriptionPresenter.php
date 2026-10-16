<?php

namespace App\Billing\Presenter;

use App\Billing\Enum\BillingPeriod;
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
use App\Entity\Company\CompanyPayment;
use App\Entity\Company\CompanySubscription;
use App\Repository\Company\CompanyInvoiceRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Shared\Helper\MoneyHelper;
use App\Website\Enum\PlanBilling;
use DateTimeImmutable;

class SubscriptionPresenter
{
    public const CANCEL_REASONS = [
        'price' => 'C’est trop cher pour moi',
        'volume' => 'Je n’ai pas assez de réservations',
        'pause' => 'Je ferme temporairement',
        'close' => 'Je ferme définitivement',
        'other' => 'Autre raison',
    ];

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
        $perBooking = $plan?->getBilling() === PlanBilling::PER_BOOKING;
        $vatRate = $plan?->getVatRate() ?? 20;
        $unit = $subscription->getUnitPrice();
        $end = $subscription->getCurrentPeriodEnd();
        $company = $subscription->getCompany();
        $pending = $company !== null ? $this->subscriptions->findPending($company) : null;
        $successor = $pending !== null && $pending->getPrevious() === $subscription ? $pending : null;
        $promo = $subscription->getPromoCode();
        $establishments = $company !== null ? $this->subscriptionService->billableEstablishments($company) : [];
        $covered = $perBooking ? 0 : count(array_filter(array_keys($establishments), fn (int $index): bool => $this->subscriptionService->covers($subscription, $index)));

        return [
            'id' => $subscription->getId(),
            'status' => $subscription->getStatus()->value,
            'statusLabel' => $subscription->getStatus()->label(),
            'plan' => ['code' => $plan?->getCode(), 'name' => $plan?->getName(), 'perBooking' => $perBooking],
            'period' => $subscription->getPeriod()->value,
            'periodLabel' => $subscription->getPeriod()->label(),
            'priceLine' => $perBooking
                ? sprintf('%s HT par créneau réservé', MoneyHelper::formatExact($unit))
                : sprintf('%s HT / %s · %s TTC', MoneyHelper::formatExact($unit), $subscription->getPeriod() === BillingPeriod::YEARLY ? 'an' : 'mois', MoneyHelper::formatExact(MoneyHelper::includingTax($unit, $vatRate))),
            'nextDate' => PeriodHelper::shortDay($end),
            'commitment' => ($plan?->getCommitmentMonths() ?? 0) > 0 ? sprintf('engagement %d mois', $plan->getCommitmentMonths()) : 'sans engagement',
            'cancelAt' => PeriodHelper::shortDay($subscription->getCancelAt()),
            'pausedUntil' => PeriodHelper::shortDay($subscription->getPausedUntil()),
            'promo' => $promo !== null && $subscription->getPromoEndsAt() !== null && $subscription->getPromoEndsAt() > $now
                ? sprintf('Code %s : %s, jusqu\'au %s', $promo->getCode(), $this->promos->describe($promo), PeriodHelper::shortDay($subscription->getPromoEndsAt()))
                : null,
            'nextInvoice' => !$perBooking && $end !== null && $successor === null && $subscription->getStatus() !== SubscriptionStatus::PAUSED ? [
                'date' => PeriodHelper::shortDay($end),
                'amount' => MoneyHelper::formatExact(MoneyHelper::includingTax($this->prices->amountFor($subscription, $end), $vatRate)) . ' TTC',
                'label' => sprintf('%s, %s', $plan?->getName(), PeriodHelper::monthName($end)),
            ] : null,
            'scheduled' => $successor !== null ? ['plan' => $successor->getPlan()?->getName(), 'date' => PeriodHelper::shortDay($end)] : null,
            'establishments' => ['total' => count($establishments), 'covered' => $covered, 'uncovered' => $perBooking ? 0 : count($establishments) - $covered],
            'canCancel' => !$perBooking && $subscription->getCancelAt() === null && $successor === null,
            'canRevoke' => $subscription->getCancelAt() !== null,
            'blockingReason' => $company !== null ? $this->access->blockingReason($company, $now) : null,
        ];
    }

    public function alert(Company $company, ?CompanySubscription $subscription): ?array
    {
        $unpaid = $this->invoices->findUnpaidForCompany($company);

        if ($unpaid === []) {
            return null;
        }

        $invoice = $unpaid[0];
        $failed = null;

        foreach ($invoice->getPayments() as $payment) {
            if ($payment instanceof CompanyPayment && in_array($payment->getStatus(), [PaymentStatus::FAILED, PaymentStatus::REQUIRES_ACTION], true)) {
                $failed = $payment;
            }
        }

        if ($failed === null) {
            return null;
        }

        return [
            'invoice' => $invoice->getNumber(),
            'amount' => MoneyHelper::formatExact($invoice->getTotalExcludingTax()) . ' HT',
            'amountIncludingTax' => MoneyHelper::formatExact($invoice->getTotalIncludingTax()),
            'failedAt' => PeriodHelper::shortDay($failed->getAttemptedAt()),
            'method' => PaymentMethodHelper::label($failed->getPaymentMethod()),
            'reason' => $failed->getStatus() === PaymentStatus::REQUIRES_ACTION ? 'validation bancaire requise' : $failed->getFailureMessage(),
            'nextRetry' => PeriodHelper::shortDay($failed->getNextRetryAt()),
            'graceUntil' => PeriodHelper::shortDay($subscription?->getGraceUntil()),
            'suspended' => $subscription !== null && $this->access->isSuspended($subscription),
        ];
    }

    public function cancellation(CompanySubscription $subscription): array
    {
        $end = $subscription->getCurrentPeriodEnd();
        $perBooking = $this->catalog->defaultPerBooking();
        $fee = MoneyHelper::formatExact($perBooking->getPrice());
        $count = $subscription->getCompany() !== null ? count($this->subscriptionService->billableEstablishments($subscription->getCompany())) : 0;
        $retention = $this->renewals->retentionPromoAvailable($subscription) ? $this->promos->find(RenewalService::RETENTION_PROMO_CODE) : null;
        $offers = [
            'price' => $retention !== null
                ? ['title' => $this->promos->describe($retention), 'text' => sprintf('Restez sur %s avec cette remise appliquée dès la prochaine facture.', $subscription->getPlan()?->getName()), 'cta' => 'Profiter de l’offre']
                : null,
            'volume' => ['title' => sprintf('Passez plutôt « %s »', $perBooking->getName()), 'text' => sprintf('Vous paierez seulement %s HT par créneau réservé, sans résilier.', $fee), 'cta' => 'Changer de formule'],
            'pause' => ['title' => sprintf('Mettre en pause %d mois', RenewalService::PAUSE_MONTHS), 'text' => 'Fermeture, congés, travaux : suspendez l’abonnement sans perdre votre page.', 'cta' => 'Mettre en pause'],
        ];

        return [
            'plan' => $subscription->getPlan()?->getName(),
            'company' => $subscription->getCompany()?->getName(),
            'activeUntil' => $end !== null ? PeriodHelper::day($end->modify('-1 day')) : null,
            'switchDate' => PeriodHelper::shortDay($end),
            'nextPlan' => $perBooking->getName(),
            'refund' => 'Aucun, période en cours due',
            'establishmentsCount' => $count,
            'keep' => [sprintf('Vos %d page%s établissement', $count, $count > 1 ? 's' : ''), 'Vos créneaux, réservations et avis', 'Votre historique et vos factures'],
            'lose' => array_values(array_filter([
                sprintf('Commission de %s HT par créneau réservé', $fee),
                $subscription->getPlan()?->isFeatured() ? 'Plus de mise en avant dans le quartier' : null,
                $subscription->getPlan()?->isAdvancedStats() ? 'Statistiques détaillées masquées' : null,
            ])),
            'reasons' => array_map(static fn (string $key, string $label): array => ['key' => $key, 'label' => $label], array_keys(self::CANCEL_REASONS), self::CANCEL_REASONS),
            'offers' => $offers,
            'acknowledgement' => sprintf('Je comprends que mes créneaux seront facturés %s HT par réservation à partir du %s.', $fee, PeriodHelper::shortDay($end)),
        ];
    }
}