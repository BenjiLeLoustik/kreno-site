<?php

namespace App\Billing\Service;

use App\Billing\Dto\RetentionOfferInput;
use App\Billing\Enum\BillingPeriod;
use App\Billing\Enum\CancelReason;
use App\Billing\Enum\SubscriptionStatus;
use App\Billing\Event\SubscriptionChangedEvent;
use App\Billing\Event\SubscriptionPausedEvent;
use App\Billing\Exception\OfferRequiresSubscriptionException;
use App\Billing\Exception\OfferUnavailableException;
use App\Billing\Exception\PauseNotAllowedException;
use App\Billing\Exception\UnknownOfferException;
use App\Billing\Helper\PeriodHelper;
use App\Entity\Company\CompanySubscription;
use App\Entity\User\User;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Website\Enum\PlanBilling;
use App\Website\Enum\PriceChangePolicy;
use App\Website\Provider\WebsiteProvider;
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
        $successor = $this->successor($subscription);

        if ($successor !== null) {
            return $this->switchTo($subscription, $successor, $end);
        }

        if ($subscription->getStatus() === SubscriptionStatus::PAUSED) {
            $subscription->setStatus(SubscriptionStatus::ACTIVE);
            $subscription->setPausedUntil(null);
        } elseif ($this->pauseStartsAt($subscription, $end)) {
            return $this->startPause($subscription, $end);
        }

        $this->refreshPrice($subscription, $now);
        $subscription->setCurrentPeriodStart($end);
        $subscription->setCurrentPeriodEnd(PeriodHelper::end($end, $subscription->getPeriod()));
        $this->orm->flush();
        $this->subscriptionService->billPeriod($subscription);

        return $subscription;
    }

    public function applyOffer(
        CompanySubscription $subscription,
        User $user,
        RetentionOfferInput $input,
    ): CompanySubscription {
        if ($subscription->getPlan()?->getBilling() !== PlanBilling::SUBSCRIPTION) {
            throw new OfferRequiresSubscriptionException();
        }

        return match (CancelReason::tryFrom((string) $input->offer)) {
            CancelReason::PRICE => $this->applyRetentionDiscount($subscription),
            CancelReason::VOLUME => $this->switchToPerBooking($subscription, $user),
            CancelReason::PAUSE => $this->pause($subscription),
            default => throw new UnknownOfferException(),
        };
    }

    public function retentionPromoAvailable(CompanySubscription $subscription): bool
    {
        $plan = $subscription->getPlan();

        if ($plan === null || $subscription->getPromoCode() !== null) {
            return false;
        }

        $promo = $this->promos->find(self::RETENTION_PROMO_CODE);

        return $promo !== null && $this->promos->isUsable($promo, $plan);
    }

    public function pause(CompanySubscription $subscription): CompanySubscription
    {
        $end = $subscription->getCurrentPeriodEnd();

        if ($subscription->getStatus() !== SubscriptionStatus::ACTIVE || $end === null) {
            throw new PauseNotAllowedException();
        }

        $subscription->setPausedUntil($end->modify(sprintf('+%d months', self::PAUSE_MONTHS)));
        $this->orm->flush();

        return $subscription;
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

    private function switchTo(
        CompanySubscription $subscription,
        CompanySubscription $successor,
        DateTimeImmutable $end,
    ): CompanySubscription {
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

    private function pauseStartsAt(CompanySubscription $subscription, DateTimeImmutable $end): bool
    {
        $pausedUntil = $subscription->getPausedUntil();

        return $pausedUntil !== null && $pausedUntil > $end;
    }

    private function startPause(CompanySubscription $subscription, DateTimeImmutable $end): CompanySubscription
    {
        $subscription->setStatus(SubscriptionStatus::PAUSED);
        $subscription->setCurrentPeriodStart($end);
        $subscription->setCurrentPeriodEnd($subscription->getPausedUntil());
        $this->orm->flush();
        $this->events->dispatch(new SubscriptionPausedEvent($subscription));

        return $subscription;
    }

    private function switchToPerBooking(CompanySubscription $subscription, User $user): CompanySubscription
    {
        $plan = $this->catalog->defaultPerBooking();
        $this->subscriptionService->schedule($subscription, $user, $plan, BillingPeriod::MONTHLY, null);

        return $subscription;
    }

    private function applyRetentionDiscount(CompanySubscription $subscription): CompanySubscription
    {
        if (!$this->retentionPromoAvailable($subscription)) {
            throw new OfferUnavailableException();
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

        if ($plan === null) {
            return;
        }

        $live = $this->catalog->find((string) $plan->getCode());

        if ($live === null || $live === $plan || $this->keepsCurrentPrice($subscription, $now)) {
            return;
        }

        $subscription->setPlan($live);
        $subscription->setUnitPrice($this->prices->planPrice($live, $subscription->getPeriod()));
    }

    private function keepsCurrentPrice(CompanySubscription $subscription, DateTimeImmutable $now): bool
    {
        $policy = $this->website->getLive()->getPriceChangePolicy();
        $startedAt = $subscription->getStartedAt();

        if ($policy !== PriceChangePolicy::KEEP_TWELVE_MONTHS || $startedAt === null) {
            return false;
        }

        return $startedAt > $now->modify('-12 months');
    }
}