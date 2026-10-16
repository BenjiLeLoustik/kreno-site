<?php

namespace App\Billing\Service;

use App\Billing\Enum\BillingPeriod;
use App\Billing\Enum\SubscriptionStatus;
use App\Billing\Dto\PlanChoiceInput;
use App\Billing\Dto\SubscriptionCancelInput;
use App\Billing\Event\SubscriptionCancellationRevokedEvent;
use App\Billing\Event\SubscriptionCancelledEvent;
use App\Billing\Event\SubscriptionChangedEvent;
use App\Billing\Event\SubscriptionStartedEvent;
use App\Billing\Helper\PeriodHelper;
use App\Billing\Model\SubscriptionChange;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanySubscription;
use App\Entity\User\User;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Establishment\Enum\EstablishmentStatus;
use App\Repository\Company\CompanyPaymentMethodRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Shared\Exception\BusinessRuleException;
use App\Website\Enum\PlanBilling;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class SubscriptionService
{
    public const REASON_CHANGED = 'changed';

    public const REASON_SWITCH = 'volume';

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
        $period = BillingPeriod::from($input->period);
        $promo = $this->promos->resolve($input->promoCode, $plan);

        if ($this->subscriptions->findCurrent($company) !== null) {
            throw new BusinessRuleException('Votre entreprise a déjà une formule active : changez-la depuis la facturation.');
        }

        $subscription = $this->subscriptions->findPending($company);

        if ($subscription === null) {
            $subscription = new CompanySubscription();
            $company->addSubscription($subscription);
            $this->orm->persist($subscription);
        }

        $subscription->setPlan($plan);
        $subscription->setPeriod($plan->getBilling() === PlanBilling::PER_BOOKING ? BillingPeriod::MONTHLY : $period);
        $subscription->setPromoCode($promo);
        $subscription->setUnitPrice($this->prices->planPrice($plan, $subscription->getPeriod()));
        $subscription->setChangedBy($user);
        $subscription->setStatus(SubscriptionStatus::PENDING);
        $this->orm->flush();

        return $subscription;
    }

    public function applyPromo(CompanySubscription $subscription, ?string $code): CompanySubscription
    {
        $plan = $subscription->getPlan() ?? throw new BusinessRuleException('Aucune formule choisie.');
        $subscription->setPromoCode($this->promos->resolve($code, $plan));
        $this->orm->flush();

        return $subscription;
    }

    public function activatePending(Company $company): ?CompanySubscription
    {
        $subscription = $this->subscriptions->findPending($company);

        if ($subscription === null || $subscription->getPrevious() !== null || $this->subscriptions->findCurrent($company) !== null) {
            return null;
        }

        $now = new DateTimeImmutable();
        $plan = $subscription->getPlan() ?? throw new BusinessRuleException('Aucune formule choisie.');
        $trial = $plan->getBilling() === PlanBilling::SUBSCRIPTION && $plan->getTrialDays() > 0;

        $this->start($subscription, $now, $trial ? $now->modify(sprintf('+%d days', $plan->getTrialDays())) : PeriodHelper::end($now, $subscription->getPeriod()));
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
        $period = $plan->getBilling() === PlanBilling::PER_BOOKING ? BillingPeriod::MONTHLY : BillingPeriod::from($input->period);
        $promo = $this->promos->resolve($input->promoCode, $plan);

        if ($current->getPlan()?->getCode() === $plan->getCode() && $current->getPeriod() === $period) {
            throw new BusinessRuleException('Vous êtes déjà sur cette formule.');
        }

        if ($plan->getBilling() === PlanBilling::PER_BOOKING) {
            $this->schedule($current, $user, $plan, $period, null);

            return new SubscriptionChange($current, true);
        }

        $method = $input->paymentMethodId !== null ? $this->methods->findOneForCompany($company, $input->paymentMethodId) : $this->methods->findDefault($company);

        if ($method === null) {
            throw new BusinessRuleException('Ajoutez un moyen de paiement pour changer de formule.');
        }

        $now = new DateTimeImmutable();
        $quote = $this->prices->prorata($current, $plan, $period, $promo, $now);

        $subscription = $this->replace($current, $user, $plan, $period, $promo, $now, $quote['periodStart'], $quote['periodEnd']);
        $invoice = null;
        $attempt = null;

        if ($quote['charge'] > 0) {
            $lines = [['label' => sprintf('%s, prorata du %s au %s', $plan->getName(), PeriodHelper::day($now), PeriodHelper::day($quote['periodEnd'])), 'unitPrice' => $quote['charge']]];

            if ($quote['credit'] > 0) {
                $lines[] = ['label' => sprintf('Crédit %s non utilisé', $current->getPlan()?->getName()), 'unitPrice' => -min($quote['credit'], $quote['charge'])];
            }

            $invoice = $this->invoices->issue($company, $lines, $now, $quote['periodEnd'], $subscription);

            if ($invoice->getTotalIncludingTax() > 0) {
                $attempt = $this->payments->collect($invoice, $method, $user, false);
            }
        }

        $this->events->dispatch(new SubscriptionChangedEvent($subscription, $current));

        return new SubscriptionChange($subscription, false, $invoice, $attempt);
    }

    public function schedule(CompanySubscription $current, User $user, WebsitePlan $plan, BillingPeriod $period, ?WebsitePromoCode $promo): CompanySubscription
    {
        $company = $current->getCompany() ?? throw new BusinessRuleException('Abonnement sans entreprise.');
        $pending = $this->subscriptions->findPending($company);

        if ($pending === null || $pending->getPrevious() !== $current) {
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

    public function cancel(CompanySubscription $subscription, User $user, SubscriptionCancelInput $input): CompanySubscription
    {
        if ($subscription->getPlan()?->getBilling() !== PlanBilling::SUBSCRIPTION) {
            throw new BusinessRuleException('Votre formule est sans abonnement : il n\'y a rien à résilier.');
        }

        if ($subscription->getCancelAt() !== null) {
            throw new BusinessRuleException('La résiliation est déjà enregistrée.');
        }

        $comment = trim((string) $input->comment);
        $subscription->setCancellationReason($input->reason);
        $subscription->setCancellationComment($comment !== '' ? $comment : null);
        $this->schedule($subscription, $user, $this->catalog->defaultPerBooking(), BillingPeriod::MONTHLY, null);

        $this->events->dispatch(new SubscriptionCancelledEvent($subscription));

        return $subscription;
    }

    public function revokeCancellation(CompanySubscription $subscription): CompanySubscription
    {
        if ($subscription->getCancelAt() === null) {
            throw new BusinessRuleException('Aucune résiliation n\'est prévue.');
        }

        $pending = $subscription->getCompany() !== null ? $this->subscriptions->findPending($subscription->getCompany()) : null;

        if ($pending !== null && $pending->getPrevious() === $subscription) {
            $subscription->getCompany()?->removeSubscription($pending);
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
        $subscription->setPromoEndsAt($promo !== null ? $start->modify(sprintf('+%d months', $this->promos->months($promo))) : null);

        if ($promo !== null) {
            $this->promos->consume($promo);
        }
    }

    public function replace(CompanySubscription $current, ?User $user, WebsitePlan $plan, BillingPeriod $period, ?WebsitePromoCode $promo, DateTimeImmutable $now, DateTimeImmutable $periodStart, DateTimeImmutable $periodEnd): CompanySubscription
    {
        $company = $current->getCompany() ?? throw new BusinessRuleException('Abonnement sans entreprise.');
        $pending = $this->subscriptions->findPending($company);

        if ($pending !== null && $pending->getPrevious() === $current) {
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
            sprintf('%s, %s', $plan->getName(), $subscription->getPeriod() === BillingPeriod::YEARLY ? PeriodHelper::label($start, $end) : PeriodHelper::monthName($start) . ' ' . $start->format('Y')),
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
        $establishments = array_filter($company->getEstablishments()->toArray(), static fn (CompanyEstablishment $establishment): bool => in_array($establishment->getStatus(), [EstablishmentStatus::ONLINE, EstablishmentStatus::PAUSED, EstablishmentStatus::PENDING_REVIEW], true));
        usort($establishments, static fn (CompanyEstablishment $a, CompanyEstablishment $b): int => [$a->getCreatedAt(), $a->getId()] <=> [$b->getCreatedAt(), $b->getId()]);

        return array_values($establishments);
    }
}