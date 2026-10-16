<?php

namespace App\Billing\Service;

use App\Billing\Enum\PublicationBlock;
use App\Billing\Enum\SubscriptionStatus;
use App\Billing\Event\SubscriptionSuspendedEvent;
use App\Billing\Exception\NoOverduePaymentException;
use App\Billing\Exception\PublicationBlockedException;
use App\Entity\Company\Company;
use App\Entity\Company\CompanySubscription;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Service\SlotPublicationService;
use App\Repository\Company\CompanySubscriptionRepository;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class BillingAccessService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private EventManagerInterface $events,
        private CompanySubscriptionRepository $subscriptions,
    ) {
    }

    public function blockingReason(Company $company, ?DateTimeImmutable $now = null): ?string
    {
        return $this->block($company, $now)?->message();
    }

    public function block(Company $company, ?DateTimeImmutable $now = null): ?PublicationBlock
    {
        $subscription = $this->subscriptions->findCurrent($company);

        if ($subscription === null) {
            return PublicationBlock::NO_PLAN;
        }

        if ($subscription->getStatus() === SubscriptionStatus::PAUSED) {
            return PublicationBlock::PAUSED;
        }

        if ($this->isSuspended($subscription, $now)) {
            return PublicationBlock::SUSPENDED;
        }

        return null;
    }

    public function assertCanPublish(Company $company): void
    {
        $block = $this->block($company);

        if ($block !== null) {
            throw new PublicationBlockedException($block);
        }
    }

    public function isSuspended(CompanySubscription $subscription, ?DateTimeImmutable $now = null): bool
    {
        if ($subscription->getStatus() !== SubscriptionStatus::PAST_DUE) {
            return false;
        }

        $graceUntil = $subscription->getGraceUntil();

        if ($graceUntil === null) {
            return true;
        }

        return $graceUntil <= ($now ?? new DateTimeImmutable());
    }

    public function enforceGrace(SlotPublicationService $publication, ?DateTimeImmutable $now = null): int
    {
        $subscriptions = $this->subscriptions->findPastDueBeyondGrace($now ?? new DateTimeImmutable());

        foreach ($subscriptions as $subscription) {
            $this->withdrawSlots($publication, $subscription);
            $subscription->setGraceUntil(null);
            $this->orm->flush();
            $this->events->dispatch(new SubscriptionSuspendedEvent($subscription));
        }

        return count($subscriptions);
    }

    public function grant(CompanySubscription $subscription, int $days): CompanySubscription
    {
        if ($subscription->getStatus() !== SubscriptionStatus::PAST_DUE) {
            throw new NoOverduePaymentException();
        }

        $now = new DateTimeImmutable();
        $base = max($now, $subscription->getGraceUntil() ?? $now);
        $subscription->setGraceUntil($base->modify(sprintf('+%d days', $days)));
        $this->orm->flush();

        return $subscription;
    }

    private function withdrawSlots(SlotPublicationService $publication, CompanySubscription $subscription): void
    {
        $company = $subscription->getCompany();

        if ($company === null) {
            return;
        }

        foreach ($company->getEstablishments() as $establishment) {
            if ($establishment->getStatus() === EstablishmentStatus::ONLINE) {
                $publication->withdrawAll($establishment);
            }
        }
    }
}