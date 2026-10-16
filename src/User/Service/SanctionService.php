<?php

namespace App\User\Service;

use App\Entity\User\User;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Repository\BookingRepository;
use App\Repository\User\UserRepository;
use App\Shared\Exception\BusinessRuleException;
use App\User\Enum\NotificationType;
use App\User\Enum\UserStatus;
use App\User\Event\UserSuspendedEvent;
use App\User\Helper\UserNameHelper;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class SanctionService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private BookingRepository $bookings,
        private UserRepository $users,
        private WebsiteProvider $website,
        private NotificationService $notifications,
        private ActivityLogger $logger,
        private EventManagerInterface $events,
    ) {
    }

    public function applyNoShowPolicy(User $customer): bool
    {
        if ($customer->getStatus() !== UserStatus::ACTIVE) {
            return false;
        }

        $rule = $this->website->getRule();
        $since = new DateTimeImmutable(sprintf('-%d days', $rule->getNoShowWindowDays()));
        $noShows = $this->bookings->countNoShowsSince($customer, $since);

        if ($noShows < $rule->getToleratedNoShows()) {
            return false;
        }

        $this->suspend($customer, $rule->getSuspensionDays(), sprintf('%d absences non prévenues en %d jours', $noShows, $rule->getNoShowWindowDays()), null, true);

        return true;
    }

    public function suspend(User $user, int $days, string $reason, ?User $actor = null, bool $automatic = false): User
    {
        if ($user->getStatus() === UserStatus::BANNED || $user->getStatus() === UserStatus::DELETED) {
            throw new BusinessRuleException('Ce compte ne peut pas être suspendu.');
        }

        $until = new DateTimeImmutable(sprintf('+%d days', $days));

        $user->setStatus(UserStatus::SUSPENDED)->setSuspendedUntil($until)->setStatusReason($reason);

        $this->notifications->notify($user, NotificationType::INFO, sprintf('Compte suspendu jusqu\'au %s', $until->format('d/m/Y')), $reason, null, false);
        $this->logger->log(ActivityType::SANCTION, sprintf('%s suspendu %d jours%s', UserNameHelper::shortName($user), $days, $automatic ? ' (automatique)' : ''), $actor, null, $user, ['reason' => $reason]);
        $this->orm->flush();

        $this->events->dispatch(new UserSuspendedEvent($user, $reason, $automatic));

        return $user;
    }

    public function lift(User $user, ?User $actor = null): User
    {
        if ($user->getStatus() !== UserStatus::SUSPENDED && $user->getStatus() !== UserStatus::BANNED) {
            return $user;
        }

        $user->setStatus(UserStatus::ACTIVE)->setSuspendedUntil(null)->setStatusReason(null);

        $this->logger->log(ActivityType::SANCTION, sprintf('Sanction levée pour %s', UserNameHelper::shortName($user)), $actor, null, $user);
        $this->orm->flush();

        return $user;
    }

    public function ban(User $user, string $reason, ?User $actor = null): User
    {
        if ($user->getStatus() === UserStatus::DELETED) {
            throw new BusinessRuleException('Ce compte a été supprimé.');
        }

        $user->setStatus(UserStatus::BANNED)->setSuspendedUntil(null)->setStatusReason($reason);

        $this->logger->log(ActivityType::SANCTION, sprintf('%s banni', UserNameHelper::shortName($user)), $actor, null, $user, ['reason' => $reason]);
        $this->orm->flush();

        return $user;
    }

    public function liftExpired(DateTimeImmutable $now): int
    {
        $count = 0;

        foreach ($this->users->findSuspensionsEndedBefore($now) as $user) {
            $user->setStatus(UserStatus::ACTIVE)->setSuspendedUntil(null)->setStatusReason(null);
            $count++;
        }

        $this->orm->flush();

        return $count;
    }
}