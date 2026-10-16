<?php

namespace App\Billing\Service;

use App\Billing\Enum\SubscriptionStatus;
use App\Billing\Event\SubscriptionSuspendedEvent;
use App\Entity\Company\Company;
use App\Entity\Company\CompanySubscription;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Service\SlotPublicationService;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Shared\Exception\BusinessRuleException;
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
        $subscription = $this->subscriptions->findCurrent($company);

        if ($subscription === null) {
            return 'Choisissez une formule pour publier des créneaux.';
        }

        if ($subscription->getStatus() === SubscriptionStatus::PAUSED) {
            return 'Votre abonnement est en pause : la publication de créneaux reprendra à la fin de la pause.';
        }

        if ($this->isSuspended($subscription, $now)) {
            return 'Un paiement est en attente depuis trop longtemps : réglez la facture pour publier à nouveau.';
        }

        return null;
    }

    public function assertCanPublish(Company $company): void
    {
        $reason = $this->blockingReason($company);

        if ($reason !== null) {
            throw new BusinessRuleException($reason);
        }
    }

    public function isSuspended(CompanySubscription $subscription, ?DateTimeImmutable $now = null): bool
    {
        return $subscription->getStatus() === SubscriptionStatus::PAST_DUE
            && ($subscription->getGraceUntil() === null || $subscription->getGraceUntil() <= ($now ?? new DateTimeImmutable()));
    }

    public function enforceGrace(SlotPublicationService $publication, ?DateTimeImmutable $now = null): int
    {
        $subscriptions = $this->subscriptions->findPastDueBeyondGrace($now ?? new DateTimeImmutable());

        foreach ($subscriptions as $subscription) {
            foreach ($subscription->getCompany()?->getEstablishments() ?? [] as $establishment) {
                if ($establishment->getStatus() === EstablishmentStatus::ONLINE) {
                    $publication->withdrawAll($establishment);
                }
            }

            $subscription->setGraceUntil(null);
            $this->orm->flush();
            $this->events->dispatch(new SubscriptionSuspendedEvent($subscription));
        }

        return count($subscriptions);
    }

    public function grant(CompanySubscription $subscription, int $days): CompanySubscription
    {
        if ($subscription->getStatus() !== SubscriptionStatus::PAST_DUE) {
            throw new BusinessRuleException('Aucun paiement n\'est en retard.');
        }

        $base = max(new DateTimeImmutable(), $subscription->getGraceUntil() ?? new DateTimeImmutable());
        $subscription->setGraceUntil($base->modify(sprintf('+%d days', $days)));
        $this->orm->flush();

        return $subscription;
    }
}