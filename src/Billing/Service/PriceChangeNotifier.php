<?php

namespace App\Billing\Service;

use App\Billing\Enum\SubscriptionStatus;
use App\Billing\Event\PriceChangeAnnouncedEvent;
use App\Billing\Event\PriceChangeCancelledEvent;
use App\Billing\Exception\PriceChangeNoticeTooShortException;
use App\Billing\Helper\PeriodHelper;
use App\Entity\Company\CompanyPriceNotice;
use App\Entity\Company\CompanySubscription;
use App\Entity\Website\WebsiteConfig;
use App\Entity\Website\WebsitePlan;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Repository\Company\CompanyPriceNoticeRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Website\Enum\PriceChangePolicy;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class PriceChangeNotifier
{
    public const STALE_HOURS = 24;

    private const BATCH_SIZE = 200;

    public function __construct(
        private CompanySubscriptionRepository $subscriptions,
        private CompanyPriceNoticeRepository $notices,
        private PriceCalculator $prices,
        private EventManagerInterface $events,
        private ActivityLogger $logger,
        private OrmManagerInterface $orm,
    ) {
    }

    public function changes(WebsiteConfig $version, DateTimeImmutable $goLiveAt): array
    {
        $plans = $this->plansByCode($version);
        $changes = [];
        $offset = 0;

        do {
            $batch = $this->subscriptions->findAllCurrent(self::BATCH_SIZE, $offset);
            $offset += self::BATCH_SIZE;

            foreach ($batch as $subscription) {
                $plan = $plans[$subscription->getPlan()?->getCode()] ?? null;
                $change = $this->changeFor($subscription, $plan, $version, $goLiveAt);

                if ($change !== null) {
                    $changes[] = $change;
                }
            }
        } while (count($batch) === self::BATCH_SIZE);

        return $changes;
    }

    public function assertNotice(
        WebsiteConfig $version,
        DateTimeImmutable $goLiveAt,
        ?DateTimeImmutable $now = null,
    ): void {
        $now ??= new DateTimeImmutable();
        $days = $version->getPriceChangeNoticeDays();

        if ($days <= 0 || $goLiveAt >= $now->modify(sprintf('+%d days', $days))) {
            return;
        }

        $increases = $this->countIncreases($this->changes($version, $goLiveAt));

        if ($increases > 0) {
            throw new PriceChangeNoticeTooShortException((string) $version->getCode(), $increases, $days);
        }
    }

    public function announce(
        WebsiteConfig $version,
        ?DateTimeImmutable $goLiveAt = null,
        ?DateTimeImmutable $now = null,
    ): int {
        $now ??= new DateTimeImmutable();
        $pending = $this->pendingBySubscription($version, $now);
        $dispatch = [];
        $counts = [
            'new' => 0,
            'corrected' => 0,
            'cancelled' => 0
        ];

        foreach ($this->changes($version, $goLiveAt ?? $version->getScheduledAt() ?? $now) as $change) {
            $subscriptionId = $change->getSubscription()->getId();
            $notices = $pending[$subscriptionId] ?? [];
            unset($pending[$subscriptionId]);

            $reference = $this->lastNotice($notices);

            if ($reference === null) {
                $dispatch[] = $change;
                ++$counts['new'];
            } elseif ($this->differs($reference, $change)) {
                $dispatch[] = $change->correct($reference->getNewPrice(), $reference->getEffectiveAt());
                ++$counts['corrected'];
            }

            $this->keepOnlyNoticeOf($version, $change, $notices);
        }

        foreach ($pending as $notices) {
            $dispatch[] = $this->cancel($notices);
            ++$counts['cancelled'];
        }

        if ($dispatch !== []) {
            $this->logAnnouncement($version, $counts);
        }

        $this->orm->flush();
        $this->dispatchAll($dispatch);

        return count($dispatch);
    }

    public function cancelStale(?DateTimeImmutable $now = null): int
    {
        $now ??= new DateTimeImmutable();
        $stale = [];
        $dispatch = [];

        foreach ($this->notices->findStale($now->modify(sprintf('-%d hours', self::STALE_HOURS)), $now) as $notice) {
            $stale[$notice->getSubscription()?->getId()][] = $notice;
        }

        foreach ($stale as $notices) {
            $dispatch[] = $this->cancel($notices);
        }

        foreach ($this->notices->findPast($now) as $notice) {
            $this->orm->remove($notice);
        }

        $this->orm->flush();
        $this->dispatchAll($dispatch);

        return count($dispatch);
    }

    private function plansByCode(WebsiteConfig $version): array
    {
        $plans = [];

        foreach ($version->getPlans() as $plan) {
            $plans[$plan->getCode()] = $plan;
        }

        return $plans;
    }

    private function countIncreases(array $changes): int
    {
        $count = 0;

        foreach ($changes as $change) {
            if ($change->getNewPrice() > $change->getCurrentPrice()) {
                ++$count;
            }
        }

        return $count;
    }

    private function lastNotice(array $notices): ?CompanyPriceNotice
    {
        if ($notices === []) {
            return null;
        }

        return $notices[array_key_last($notices)];
    }

    private function differs(CompanyPriceNotice $notice, PriceChangeAnnouncedEvent $change): bool
    {
        if ($notice->getNewPrice() !== $change->getNewPrice()) {
            return true;
        }

        return $notice->getEffectiveAt() != $change->getEffectiveAt();
    }

    private function logAnnouncement(WebsiteConfig $version, array $counts): void
    {
        $message = sprintf(
            'Annonce tarifaire : %d nouvelle(s), %d correction(s), %d annulation(s)',
            $counts['new'],
            $counts['corrected'],
            $counts['cancelled'],
        );

        $this->logger->log(ActivityType::PRICING, $message, null, $version, $version, $counts);
    }

    private function dispatchAll(array $events): void
    {
        foreach ($events as $event) {
            $this->events->dispatch($event);
        }
    }

    private function pendingBySubscription(WebsiteConfig $version, DateTimeImmutable $now): array
    {
        $pending = [];

        foreach ($this->notices->findPendingFromUnpublished($now, $version) as $notice) {
            $pending[$notice->getSubscription()?->getId()][] = $notice;
        }

        return $pending;
    }

    private function keepOnlyNoticeOf(WebsiteConfig $version, PriceChangeAnnouncedEvent $change, array $notices): void
    {
        $own = null;

        foreach ($notices as $notice) {
            if ($notice->getWebsite() === $version) {
                $own = $notice;
            } else {
                $this->orm->remove($notice);
            }
        }

        if ($own === null) {
            $own = (new CompanyPriceNotice())
                ->setSubscription($change->getSubscription())
                ->setWebsite($version);
            $this->orm->persist($own);
        }

        $own
            ->setCurrentPrice($change->getCurrentPrice())
            ->setNewPrice($change->getNewPrice())
            ->setEffectiveAt($change->getEffectiveAt());
    }

    private function cancel(array $notices): PriceChangeCancelledEvent
    {
        $reference = $notices[array_key_last($notices)];
        $event = new PriceChangeCancelledEvent(
            $reference->getSubscription(),
            $reference->getNewPrice(),
            $reference->getEffectiveAt(),
        );

        foreach ($notices as $notice) {
            $this->orm->remove($notice);
        }

        return $event;
    }

    private function changeFor(
        CompanySubscription $subscription,
        ?WebsitePlan $plan,
        WebsiteConfig $version,
        DateTimeImmutable $goLiveAt,
    ): ?PriceChangeAnnouncedEvent {
        if ($plan === null || $plan === $subscription->getPlan()) {
            return null;
        }

        if ($subscription->getStatus() === SubscriptionStatus::PENDING) {
            return null;
        }

        $currentPrice = $subscription->getUnitPrice();
        $newPrice = $this->prices->planPrice($plan, $subscription->getPeriod());

        if ($newPrice === $currentPrice) {
            return null;
        }

        $effectiveAt = $this->effectiveAt($subscription, $version, $goLiveAt);

        if ($effectiveAt === null || $this->endsBefore($subscription, $effectiveAt)) {
            return null;
        }

        return new PriceChangeAnnouncedEvent($subscription, $plan, $currentPrice, $newPrice, $effectiveAt);
    }

    private function endsBefore(CompanySubscription $subscription, DateTimeImmutable $date): bool
    {
        $cancelAt = $subscription->getCancelAt();

        return $cancelAt !== null && $cancelAt <= $date;
    }

    private function effectiveAt(
        CompanySubscription $subscription,
        WebsiteConfig $version,
        DateTimeImmutable $goLiveAt,
    ): ?DateTimeImmutable {
        $end = $subscription->getCurrentPeriodEnd();

        if ($end === null) {
            return null;
        }

        $threshold = $goLiveAt;
        $startedAt = $subscription->getStartedAt();

        if ($version->getPriceChangePolicy() === PriceChangePolicy::KEEP_TWELVE_MONTHS && $startedAt !== null) {
            $threshold = max($threshold, $startedAt->modify('+12 months'));
        }

        while ($end < $threshold) {
            $end = PeriodHelper::end($end, $subscription->getPeriod());
        }

        return $end;
    }
}