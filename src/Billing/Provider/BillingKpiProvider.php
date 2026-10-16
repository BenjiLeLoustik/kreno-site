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

        return [
            $this->cashed($from, $to),
            $this->subscribedCompanies($from, $to),
            $this->failedPayments($from, $to),
            $this->creditNotes($from, $to),
        ];
    }

    public function pricing(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        [$from, $to] = PeriodHelper::monthBounds($now);
        $subscriptions = $this->subscriptions->findActiveByBilling(PlanBilling::SUBSCRIPTION);

        return [
            $this->monthlyRecurringRevenue($subscriptions),
            $this->commissions($now),
            $this->subscribedEstablishments($subscriptions),
            $this->cancellations($from, $to, count($subscriptions)),
        ];
    }

    private function cashed(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $value = MoneyHelper::format($this->invoices->sumPaidBetween($from, $to));

        return $this->kpi(
            'cashed',
            'Encaissé ce mois',
            $value,
            'abonnements et commissions'
        );
    }

    private function subscribedCompanies(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $count = count($this->subscriptions->findActiveByBilling(PlanBilling::SUBSCRIPTION));
        $started = $this->subscriptions->countStartedBetween($from, $to, PlanBilling::SUBSCRIPTION);

        return $this->kpi(
            'subscribed',
            'Entreprises abonnées',
            (string) $count,
            sprintf('+%d ce mois', $started)
        );
    }

    private function failedPayments(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $count = $this->payments->countFailedBetween($from, $to);

        return $this->kpi(
            'failed',
            'Paiements refusés',
            (string) $count,
            'relances automatiques J+3, J+7',
            'danger'
        );
    }

    private function creditNotes(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $value = MoneyHelper::format(abs($this->invoices->sumCreditNotesBetween($from, $to)));
        $count = $this->invoices->countCreditNotesBetween($from, $to);
        $note = sprintf('%d avoir', $count);

        if ($count > 1) {
            $note = sprintf('%d avoirs', $count);
        }

        return $this->kpi(
            'credits',
            'Avoirs émis',
            $value,
            $note
        );
    }

    private function monthlyRecurringRevenue(array $subscriptions): array
    {
        $mrr = 0;

        foreach ($subscriptions as $subscription) {
            $mrr += $this->monthlyPrice($subscription);
        }

        $count = count($subscriptions);
        $note = sprintf('%d abonnement actif', $count);

        if ($count > 1) {
            $note = sprintf('%d abonnements actifs', $count);
        }

        return $this->kpi(
            'mrr',
            'Revenu mensuel récurrent',
            MoneyHelper::format($mrr),
            $note
        );
    }

    private function monthlyPrice(CompanySubscription $subscription): int
    {
        if ($subscription->getPeriod() === BillingPeriod::YEARLY) {
            return (int) round($subscription->getUnitPrice() / 12);
        }

        return $subscription->getUnitPrice();
    }

    private function commissions(DateTimeImmutable $now): array
    {
        [$from, $to] = PeriodHelper::monthBounds($now);
        [$previousFrom, $previousTo] = PeriodHelper::previousMonthBounds($now);
        $current = MoneyHelper::format($this->bookings->sumFeesBetween($from, $to));
        $previous = MoneyHelper::format($this->bookings->sumFeesBetween($previousFrom, $previousTo));
        $booked = $this->bookings->countFeesBetween($from, $to);
        $note = sprintf('%d créneaux réservés · %s le mois dernier', $booked, $previous);

        return $this->kpi(
            'commissions',
            'Commissions du mois',
            $current,
            $note
        );
    }

    private function subscribedEstablishments(array $subscriptions): array
    {
        $covered = 0;

        foreach ($subscriptions as $subscription) {
            $covered += $this->coveredEstablishments($subscription);
        }

        $online = $this->establishments->count(['status' => EstablishmentStatus::ONLINE]);
        $note = sprintf('sur %d actifs', $online);

        return $this->kpi(
            'subscribed',
            'Établissements abonnés',
            (string) $covered,
            $note
        );
    }

    private function coveredEstablishments(CompanySubscription $subscription): int
    {
        $company = $subscription->getCompany();

        if ($company === null) {
            return 0;
        }

        $covered = 0;

        foreach (array_keys($this->subscriptionService->billableEstablishments($company)) as $index) {
            if ($this->subscriptionService->covers($subscription, $index)) {
                ++$covered;
            }
        }

        return $covered;
    }

    private function cancellations(DateTimeImmutable $from, DateTimeImmutable $to, int $active): array
    {
        $canceled = $this->subscriptions->countCanceledBetween($from, $to);
        $tone = 'default';

        if ($canceled > 0) {
            $tone = 'danger';
        }

        $note = sprintf('%s %% ce mois', $this->cancellationRate($canceled, $active + $canceled));

        return $this->kpi(
            'canceled',
            'Résiliations',
            (string) $canceled,
            $note,
            $tone
        );
    }

    private function cancellationRate(int $canceled, int $base): string
    {
        if ($base === 0) {
            return '0';
        }

        return number_format($canceled / $base * 100, 1, ',', ' ');
    }

    private function kpi(string $key, string $label, string $value, string $note, string $tone = 'default'): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'note' => $note,
            'tone' => $tone
        ];
    }
}