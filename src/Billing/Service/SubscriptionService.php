<?php

namespace App\Billing\Service;

use App\Billing\Dto\PlanChoiceInput;
use App\Billing\Dto\SubscriptionCancelInput;
use App\Billing\Enum\BillingPeriod;
use App\Billing\Enum\CancelReason;
use App\Billing\Enum\SubscriptionStatus;
use App\Billing\Event\SubscriptionCancellationRevokedEvent;
use App\Billing\Event\SubscriptionCancelledEvent;
use App\Billing\Event\SubscriptionChangedEvent;
use App\Billing\Event\SubscriptionStartedEvent;
use App\Billing\Exception\ActiveSubscriptionExistsException;
use App\Billing\Exception\CancellationAlreadyScheduledException;
use App\Billing\Exception\NoCancellationScheduledException;
use App\Billing\Exception\NoPlanChosenException;
use App\Billing\Exception\NothingToCancelException;
use App\Billing\Exception\PaymentMethodRequiredException;
use App\Billing\Exception\SamePlanException;
use App\Billing\Exception\SubscriptionWithoutCompanyException;
use App\Billing\Helper\PeriodHelper;
use App\Billing\Model\PaymentAttempt;
use App\Billing\Model\SubscriptionChange;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyInvoice;
use App\Entity\Company\CompanyPaymentMethod;
use App\Entity\Company\CompanySubscription;
use App\Entity\User\User;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Establishment\Enum\EstablishmentStatus;
use App\Repository\Company\CompanyPaymentMethodRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Shared\Helper\Translation;
use App\Website\Enum\PlanBilling;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class SubscriptionService
{
    public const REASON_CHANGED = 'changed';

    public const REASON_SWITCH = CancelReason::VOLUME->value;

    public const BILLABLE_STATUSES = [
        EstablishmentStatus::ONLINE,
        EstablishmentStatus::PAUSED,
        EstablishmentStatus::PENDING_REVIEW,
    ];

    public function __construct(
        private OrmManagerInterface $orm,
        private EventManagerInterface $events,
        private CompanySubscriptionRepository $subscriptions,
        private CompanyPaymentMethodRepository $methods,
        private PlanCatalog $catalog,
        private PromoCodeService $promos,
        private PriceCalculator $prices,
        private InvoiceService $invoices,
        private PaymentService $payments,
    ) {
    }

    public function current(Company $company): ?CompanySubscription
    {
        return $this->subscriptions->findCurrent($company);
    }

    public function choose(Company $company, User $user, PlanChoiceInput $input): CompanySubscription
    {
        $plan = $this->catalog->get((string) $input->plan);
        $period = $this->period($plan, $input->period);
        $promo = $this->promos->resolve($input->promoCode, $plan);

        if ($this->subscriptions->findCurrent($company) !== null) {
            throw new ActiveSubscriptionExistsException();
        }

        $subscription = $this->subscriptions->findPending($company);

        if ($subscription === null) {
            $subscription = new CompanySubscription();
            $company->addSubscription($subscription);
            $this->orm->persist($subscription);
        }

        $subscription->setPlan($plan);
        $subscription->setPeriod($period);
        $subscription->setPromoCode($promo);
        $subscription->setUnitPrice($this->prices->planPrice($plan, $period));
        $subscription->setChangedBy($user);
        $subscription->setStatus(SubscriptionStatus::PENDING);
        $this->orm->flush();

        return $subscription;
    }

    public function applyPromo(CompanySubscription $subscription, ?string $code): CompanySubscription
    {
        $plan = $subscription->getPlan() ?? throw new NoPlanChosenException();
        $subscription->setPromoCode($this->promos->resolve($code, $plan));
        $this->orm->flush();

        return $subscription;
    }

    public function activatePending(Company $company): ?CompanySubscription
    {
        $subscription = $this->subscriptions->findPending($company);

        if ($subscription === null || $subscription->getPrevious() !== null) {
            return null;
        }

        if ($this->subscriptions->findCurrent($company) !== null) {
            return null;
        }

        $now = new DateTimeImmutable();
        $plan = $subscription->getPlan() ?? throw new NoPlanChosenException();
        $trial = $plan->getBilling() === PlanBilling::SUBSCRIPTION && $plan->getTrialDays() > 0;
        $end = PeriodHelper::end($now, $subscription->getPeriod());

        if ($trial) {
            $end = $now->modify(sprintf('+%d days', $plan->getTrialDays()));
        }

        $this->start($subscription, $now, $end);
        $this->orm->flush();

        if (!$trial) {
            $this->billPeriod($subscription);
        }

        $this->events->dispatch(new SubscriptionStartedEvent($subscription));

        return $subscription;
    }

    public function change(Company $company, User $user, PlanChoiceInput $input): SubscriptionChange
    {
        $current = $this->subscriptions->findCurrent($company);

        if ($current === null) {
            return new SubscriptionChange($this->choose($company, $user, $input), true);
        }

        $plan = $this->catalog->get((string) $input->plan);
        $period = $this->period($plan, $input->period);
        $promo = $this->promos->resolve($input->promoCode, $plan);

        if ($current->getPlan()?->getCode() === $plan->getCode() && $current->getPeriod() === $period) {
            throw new SamePlanException();
        }

        if ($plan->getBilling() === PlanBilling::PER_BOOKING) {
            $this->schedule($current, $user, $plan, $period, null);

            return new SubscriptionChange($current, true);
        }

        $method = $this->paymentMethod($company, $input->paymentMethodId);
        $now = new DateTimeImmutable();
        $quote = $this->prices->prorata($current, $plan, $period, $promo, $now);
        $subscription = $this->replace(
            $current,
            $user,
            $plan,
            $period,
            $promo,
            $now,
            $quote['periodStart'],
            $quote['periodEnd'],
        );
        $invoice = null;
        $attempt = null;

        if ($quote['charge'] > 0) {
            $invoice = $this->invoiceChange($company, $current, $subscription, $quote, $now);
            $attempt = $this->collectChange($invoice, $method, $user);
        }

        $this->events->dispatch(new SubscriptionChangedEvent($subscription, $current));

        return new SubscriptionChange($subscription, false, $invoice, $attempt);
    }

    public function schedule(
        CompanySubscription $current,
        User $user,
        WebsitePlan $plan,
        BillingPeriod $period,
        ?WebsitePromoCode $promo,
    ): CompanySubscription {
        $company = $current->getCompany() ?? throw new SubscriptionWithoutCompanyException();
        $pending = $this->successor($company, $current);

        if ($pending === null) {
            $pending = new CompanySubscription();
            $pending->setPrevious($current);
            $company->addSubscription($pending);
            $this->orm->persist($pending);
        }

        $pending->setPlan($plan);
        $pending->setPeriod($period);
        $pending->setPromoCode($promo);
        $pending->setUnitPrice($this->prices->planPrice($plan, $period));
        $pending->setChangedBy($user);
        $pending->setStatus(SubscriptionStatus::PENDING);
        $pending->setCurrentPeriodStart($current->getCurrentPeriodEnd());

        if ($plan->getBilling() === PlanBilling::PER_BOOKING) {
            $current->setCancelAt($current->getCurrentPeriodEnd());
            $current->setCancellationReason($current->getCancellationReason() ?? self::REASON_SWITCH);
        }

        $this->orm->flush();

        return $pending;
    }

    public function cancel(
        CompanySubscription $subscription,
        User $user,
        SubscriptionCancelInput $input,
    ): CompanySubscription {
        if ($subscription->getPlan()?->getBilling() !== PlanBilling::SUBSCRIPTION) {
            throw new NothingToCancelException();
        }

        if ($subscription->getCancelAt() !== null) {
            throw new CancellationAlreadyScheduledException();
        }

        $subscription->setCancellationReason($input->reason);
        $subscription->setCancellationComment($this->comment($input->comment));
        $this->schedule(
            $subscription,
            $user,
            $this->catalog->defaultPerBooking(),
            BillingPeriod::MONTHLY,
            null
        );

        $this->events->dispatch(new SubscriptionCancelledEvent($subscription));

        return $subscription;
    }

    public function revokeCancellation(CompanySubscription $subscription): CompanySubscription
    {
        if ($subscription->getCancelAt() === null) {
            throw new NoCancellationScheduledException();
        }

        $company = $subscription->getCompany();
        $pending = $company !== null ? $this->successor($company, $subscription) : null;

        if ($pending !== null) {
            $company->removeSubscription($pending);
            $this->orm->remove($pending);
        }

        $subscription->setCancelAt(null);
        $subscription->setCancellationReason(null);
        $subscription->setCancellationComment(null);
        $this->orm->flush();

        $this->events->dispatch(new SubscriptionCancellationRevokedEvent($subscription));

        return $subscription;
    }

    public function start(CompanySubscription $subscription, DateTimeImmutable $start, DateTimeImmutable $end): void
    {
        $promo = $subscription->getPromoCode();

        $subscription->setStatus(SubscriptionStatus::ACTIVE);
        $subscription->setStartedAt($start);
        $subscription->setCurrentPeriodStart($start);
        $subscription->setCurrentPeriodEnd($end);
        $subscription->setGraceUntil(null);
        $subscription->setPromoEndsAt(null);

        if ($promo !== null) {
            $subscription->setPromoEndsAt($start->modify(sprintf('+%d months', $this->promos->months($promo))));
            $this->promos->consume($promo);
        }
    }

    public function replace(
        CompanySubscription $current,
        ?User $user,
        WebsitePlan $plan,
        BillingPeriod $period,
        ?WebsitePromoCode $promo,
        DateTimeImmutable $now,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
    ): CompanySubscription {
        $company = $current->getCompany() ?? throw new SubscriptionWithoutCompanyException();
        $pending = $this->successor($company, $current);

        if ($pending !== null) {
            $company->removeSubscription($pending);
            $this->orm->remove($pending);
        }

        $subscription = new CompanySubscription();
        $subscription->setPlan($plan);
        $subscription->setPeriod($period);
        $subscription->setPromoCode($promo);
        $subscription->setUnitPrice($this->prices->planPrice($plan, $period));
        $subscription->setPrevious($current);
        $subscription->setChangedBy($user);
        $company->addSubscription($subscription);
        $this->orm->persist($subscription);
        $this->start($subscription, $now, $periodEnd);
        $subscription->setCurrentPeriodStart($periodStart);

        $current->setStatus(SubscriptionStatus::CANCELED);
        $current->setCanceledAt($now);
        $current->setCancellationReason($current->getCancellationReason() ?? self::REASON_CHANGED);
        $current->setCancelAt(null);
        $this->orm->flush();

        return $subscription;
    }

    public function billPeriod(CompanySubscription $subscription): void
    {
        $plan = $subscription->getPlan();
        $start = $subscription->getCurrentPeriodStart();
        $end = $subscription->getCurrentPeriodEnd();

        if ($plan === null || $start === null || $end === null || $plan->getBilling() !== PlanBilling::SUBSCRIPTION) {
            return;
        }

        $invoice = $this->invoices->issueForSubscription(
            $subscription,
            $this->prices->amountFor($subscription, $start),
            sprintf('%s, %s', $plan->getName(), $this->periodLabel($subscription->getPeriod(), $start, $end)),
            $start,
            $end,
        );

        if ($invoice->getTotalIncludingTax() > 0) {
            $this->payments->collect($invoice);
        }
    }

    public function covers(CompanySubscription $subscription, int $establishmentIndex): bool
    {
        $max = $subscription->getPlan()?->getMaxEstablishments();

        return $max === null || $establishmentIndex < $max;
    }

    public function billableEstablishments(Company $company): array
    {
        $establishments = [];

        foreach ($company->getEstablishments() as $establishment) {
            if (in_array($establishment->getStatus(), self::BILLABLE_STATUSES, true)) {
                $establishments[] = $establishment;
            }
        }

        usort($establishments, static fn (CompanyEstablishment $a, CompanyEstablishment $b): int => [
                $a->getCreatedAt(),
                $a->getId(),
            ] <=> [
                $b->getCreatedAt(),
                $b->getId(),
            ]);

        return $establishments;
    }

    private function period(WebsitePlan $plan, string $period): BillingPeriod
    {
        if ($plan->getBilling() === PlanBilling::PER_BOOKING) {
            return BillingPeriod::MONTHLY;
        }

        return BillingPeriod::from($period);
    }

    private function successor(Company $company, CompanySubscription $current): ?CompanySubscription
    {
        $pending = $this->subscriptions->findPending($company);

        if ($pending === null || $pending->getPrevious() !== $current) {
            return null;
        }

        return $pending;
    }

    private function paymentMethod(Company $company, ?int $id): CompanyPaymentMethod
    {
        if ($id !== null) {
            return $this->methods->findOneForCompany($company, $id) ?? throw new PaymentMethodRequiredException();
        }

        return $this->methods->findDefault($company) ?? throw new PaymentMethodRequiredException();
    }

    private function invoiceChange(
        Company $company,
        CompanySubscription $current,
        CompanySubscription $subscription,
        array $quote,
        DateTimeImmutable $now,
    ): CompanyInvoice {
        $label = sprintf(
            Translation::translate('%s, prorata du %s au %s'),
            $subscription->getPlan()?->getName(),
            PeriodHelper::day($now),
            PeriodHelper::day($quote['periodEnd']),
        );
        $lines = [
            [
                'label' => $label,
                'unitPrice' => $quote['charge']
            ]
        ];

        if ($quote['credit'] > 0) {
            $lines[] = [
                'label' => sprintf(Translation::translate('Crédit %s non utilisé'), $current->getPlan()?->getName()),
                'unitPrice' => -min($quote['credit'], $quote['charge']),
            ];
        }

        return $this->invoices->issue($company, $lines, $now, $quote['periodEnd'], $subscription);
    }

    private function collectChange(CompanyInvoice $invoice, CompanyPaymentMethod $method, User $user): ?PaymentAttempt
    {
        if ($invoice->getTotalIncludingTax() <= 0) {
            return null;
        }

        return $this->payments->collect($invoice, $method, $user, false);
    }

    private function comment(?string $comment): ?string
    {
        $comment = trim((string) $comment);

        if ($comment === '') {
            return null;
        }

        return $comment;
    }

    private function periodLabel(BillingPeriod $period, DateTimeImmutable $start, DateTimeImmutable $end): string
    {
        if ($period === BillingPeriod::YEARLY) {
            return PeriodHelper::label($start, $end);
        }

        return PeriodHelper::monthName($start) . ' ' . $start->format('Y');
    }
}