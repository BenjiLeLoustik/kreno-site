<?php

namespace App\Billing\Service;

use App\Billing\Enum\BillingPeriod;
use App\Billing\Enum\SubscriptionStatus;
use App\Billing\Dto\RetentionOfferInput;
use App\Billing\Event\SubscriptionChangedEvent;
use App\Billing\Event\SubscriptionPausedEvent;
use App\Billing\Helper\PeriodHelper;
use App\Entity\Company\CompanySubscription;
use App\Entity\User\User;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Shared\Exception\BusinessRuleException;
use App\Website\Provider\WebsiteProvider;
use App\Website\Enum\PlanBilling;
use App\Website\Enum\PriceChangePolicy;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class RenewalService
{
    public const RETENTION_PROMO_CODE = 'FIDELITE30';

    public const PAUSE_MONTHS = 1;

    public function __construct(
        private OrmManagerInterface $orm,
        private EventManagerInterface $events,
        private WebsiteProvider $website,
        private CompanySubscriptionRepository $subscriptions,
        private SubscriptionService $subscriptionService,
        private PlanCatalog $catalog,
        private PromoCodeService $promos,
        private PriceCalculator $prices,
    ) {
    }

    public function renewDue(?DateTimeImmutable $now = null): int
    {
        $now ??= new DateTimeImmutable();
        $subscriptions = $this->subscriptions->findDueForRenewal($now);

        foreach ($subscriptions as $subscription) {
            $this->renew($subscription, $now);
        }

        return count($subscriptions);
    }

    public function renew(CompanySubscription $subscription, DateTimeImmutable $now): CompanySubscription
    {
        $end = $subscription->getCurrentPeriodEnd() ?? $now;
        $company = $subscription->getCompany();
        $successor = $company !== null ? $this->subscriptions->findPending($company) : null;

        if ($successor !== null && $successor->getPrevious() === $subscription) {
            $subscription->setStatus(SubscriptionStatus::CANCELED);
            $subscription->setCanceledAt($end);

            if ($successor->getPlan()?->getBilling() === PlanBilling::SUBSCRIPTION) {
                $subscription->setCancellationReason(SubscriptionService::REASON_CHANGED);
            }

            $this->subscriptionService->start($successor, $end, PeriodHelper::end($end, $successor->getPeriod()));
            $this->orm->flush();
            $this->subscriptionService->billPeriod($successor);
            $this->events->dispatch(new SubscriptionChangedEvent($successor, $subscription));

            return $successor;
        }

        if ($subscription->getStatus() === SubscriptionStatus::PAUSED) {
            $subscription->setStatus(SubscriptionStatus::ACTIVE);
            $subscription->setPausedUntil(null);
        } elseif ($subscription->getPausedUntil() !== null && $subscription->getPausedUntil() > $end) {
            $subscription->setStatus(SubscriptionStatus::PAUSED);
            $subscription->setCurrentPeriodStart($end);
            $subscription->setCurrentPeriodEnd($subscription->getPausedUntil());
            $this->orm->flush();
            $this->events->dispatch(new SubscriptionPausedEvent($subscription));

            return $subscription;
        }

        $this->refreshPrice($subscription, $now);
        $subscription->setCurrentPeriodStart($end);
        $subscription->setCurrentPeriodEnd(PeriodHelper::end($end, $subscription->getPeriod()));
        $this->orm->flush();
        $this->subscriptionService->billPeriod($subscription);

        return $subscription;
    }

    public function applyOffer(CompanySubscription $subscription, User $user, RetentionOfferInput $input): CompanySubscription
    {
        if ($subscription->getPlan()?->getBilling() !== PlanBilling::SUBSCRIPTION) {
            throw new BusinessRuleException('Cette offre concerne les abonnements.');
        }

        return match ($input->offer) {
            'price' => $this->applyRetentionDiscount($subscription),
            'volume' => $this->switchToPerBooking($subscription, $user),
            'pause' => $this->pause($subscription),
            default => throw new BusinessRuleException('Offre inconnue.'),
        };
    }

    public function retentionPromoAvailable(CompanySubscription $subscription): bool
    {
        $promo = $this->promos->find(self::RETENTION_PROMO_CODE);

        return $promo !== null && $subscription->getPlan() !== null && $this->promos->isUsable($promo, $subscription->getPlan()) && $subscription->getPromoCode() === null;
    }

    public function pause(CompanySubscription $subscription): CompanySubscription
    {
        if ($subscription->getStatus() !== SubscriptionStatus::ACTIVE || $subscription->getCurrentPeriodEnd() === null) {
            throw new BusinessRuleException('Seul un abonnement actif peut être mis en pause.');
        }

        $subscription->setPausedUntil($subscription->getCurrentPeriodEnd()->modify(sprintf('+%d months', self::PAUSE_MONTHS)));
        $this->orm->flush();

        return $subscription;
    }

    private function switchToPerBooking(CompanySubscription $subscription, User $user): CompanySubscription
    {
        $this->subscriptionService->schedule($subscription, $user, $this->catalog->defaultPerBooking(), BillingPeriod::MONTHLY, null);

        return $subscription;
    }

    private function applyRetentionDiscount(CompanySubscription $subscription): CompanySubscription
    {
        if (!$this->retentionPromoAvailable($subscription)) {
            throw new BusinessRuleException('Cette offre n\'est plus disponible.');
        }

        $promo = $this->promos->find(self::RETENTION_PROMO_CODE);
        $start = $subscription->getCurrentPeriodEnd() ?? new DateTimeImmutable();
        $subscription->setPromoCode($promo);
        $subscription->setPromoEndsAt($start->modify(sprintf('+%d months', $this->promos->months($promo))));
        $this->promos->consume($promo);

        return $subscription;
    }

    private function refreshPrice(CompanySubscription $subscription, DateTimeImmutable $now): void
    {
        $plan = $subscription->getPlan();
        $live = $plan !== null ? $this->catalog->find((string) $plan->getCode()) : null;

        if ($live === null || $live === $plan) {
            return;
        }

        $policy = $this->website->getLive()->getPriceChangePolicy();

        if ($policy === PriceChangePolicy::KEEP_TWELVE_MONTHS && $subscription->getStartedAt() !== null && $subscription->getStartedAt() > $now->modify('-12 months')) {
            return;
        }

        $subscription->setPlan($live);
        $subscription->setUnitPrice($this->prices->planPrice($live, $subscription->getPeriod()));
    }
}