<?php

namespace App\Billing\Provider;

use App\Billing\Enum\BillingPeriod;
use App\Billing\Helper\PeriodHelper;
use App\Billing\Service\SubscriptionService;
use App\Entity\Company\CompanySubscription;
use App\Establishment\Enum\EstablishmentStatus;
use App\Repository\BookingRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Repository\Company\CompanyInvoiceRepository;
use App\Repository\Company\CompanyPaymentRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Shared\Helper\MoneyHelper;
use App\Website\Enum\PlanBilling;
use DateTimeImmutable;

class BillingKpiProvider
{
    public function __construct(
        private CompanySubscriptionRepository $subscriptions,
        private CompanyInvoiceRepository $invoices,
        private CompanyPaymentRepository $payments,
        private CompanyEstablishmentRepository $establishments,
        private BookingRepository $bookings,
        private SubscriptionService $subscriptionService,
    ) {
    }

    public function admin(DateTimeImmutable $month): array
    {
        [$from, $to] = PeriodHelper::monthBounds($month);
        $subscribed = $this->subscriptions->findActiveByBilling(PlanBilling::SUBSCRIPTION);
        $credits = $this->invoices->sumCreditNotesBetween($from, $to);
        $creditCount = $this->invoices->countCreditNotesBetween($from, $to);

        return [
            ['key' => 'cashed', 'label' => 'Encaissé ce mois', 'value' => MoneyHelper::format($this->invoices->sumPaidBetween($from, $to)), 'note' => 'abonnements et commissions', 'tone' => 'default'],
            ['key' => 'subscribed', 'label' => 'Entreprises abonnées', 'value' => (string) count($subscribed), 'note' => sprintf('+%d ce mois', $this->subscriptions->countStartedBetween($from, $to, PlanBilling::SUBSCRIPTION)), 'tone' => 'default'],
            ['key' => 'failed', 'label' => 'Paiements refusés', 'value' => (string) $this->payments->countFailedBetween($from, $to), 'note' => 'relances automatiques J+3, J+7', 'tone' => 'danger'],
            ['key' => 'credits', 'label' => 'Avoirs émis', 'value' => MoneyHelper::format(abs($credits)), 'note' => sprintf('%d avoir%s', $creditCount, $creditCount > 1 ? 's' : ''), 'tone' => 'default'],
        ];
    }

    public function pricing(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        [$from, $to] = PeriodHelper::monthBounds($now);
        [$previousFrom, $previousTo] = PeriodHelper::previousMonthBounds($now);
        $subscriptions = $this->subscriptions->findActiveByBilling(PlanBilling::SUBSCRIPTION);
        $mrr = array_sum(array_map(static fn (CompanySubscription $subscription): int => $subscription->getPeriod() === BillingPeriod::YEARLY ? (int) round($subscription->getUnitPrice() / 12) : $subscription->getUnitPrice(), $subscriptions));
        $covered = 0;

        foreach ($subscriptions as $subscription) {
            if ($subscription->getCompany() === null) {
                continue;
            }

            foreach (array_keys($this->subscriptionService->billableEstablishments($subscription->getCompany())) as $index) {
                $covered += $this->subscriptionService->covers($subscription, $index) ? 1 : 0;
            }
        }

        $canceled = $this->subscriptions->countCanceledBetween($from, $to);
        $base = count($subscriptions) + $canceled;
        $commissions = $this->bookings->sumFeesBetween($from, $to);
        $previousCommissions = $this->bookings->sumFeesBetween($previousFrom, $previousTo);

        return [
            ['key' => 'mrr', 'label' => 'Revenu mensuel récurrent', 'value' => MoneyHelper::format($mrr), 'note' => sprintf('%d abonnement%s actif%s', count($subscriptions), count($subscriptions) > 1 ? 's' : '', count($subscriptions) > 1 ? 's' : ''), 'tone' => 'default'],
            ['key' => 'commissions', 'label' => 'Commissions du mois', 'value' => MoneyHelper::format($commissions), 'note' => sprintf('%d créneaux réservés · %s le mois dernier', $this->bookings->countFeesBetween($from, $to), MoneyHelper::format($previousCommissions)), 'tone' => 'default'],
            ['key' => 'subscribed', 'label' => 'Établissements abonnés', 'value' => (string) $covered, 'note' => sprintf('sur %d actifs', $this->establishments->count(['status' => EstablishmentStatus::ONLINE])), 'tone' => 'default'],
            ['key' => 'canceled', 'label' => 'Résiliations', 'value' => (string) $canceled, 'note' => $base > 0 ? sprintf('%s %% ce mois', number_format($canceled / $base * 100, 1, ',', ' ')) : '0 % ce mois', 'tone' => $canceled > 0 ? 'danger' : 'default'],
        ];
    }
}