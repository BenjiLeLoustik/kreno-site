<?php

namespace App\Establishment\Service;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentFollower;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Repository\Establishment\EstablishmentFollowerRepository;
use App\Shared\Exception\BusinessRuleException;
use NeoPHP\Package\Orm\OrmManagerInterface;

class FollowService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private EstablishmentFollowerRepository $followers,
    ) {
    }

    public function follow(CompanyEstablishment $establishment, User $user): EstablishmentFollower
    {
        if ($establishment->getStatus() !== EstablishmentStatus::ONLINE) {
            throw new BusinessRuleException('Cet établissement n\'est pas disponible.');
        }

        $follower = $this->followers->findOneFor($establishment, $user);

        if ($follower !== null) {
            return $follower;
        }

        $follower = new EstablishmentFollower();
        $follower->setUser($user);
        $establishment->addFollower($follower);
        $this->orm->persist($follower);
        $this->orm->flush();

        return $follower;
    }

    public function unfollow(CompanyEstablishment $establishment, User $user): void
    {
        $follower = $this->followers->findOneFor($establishment, $user);

        if ($follower !== null) {
            $establishment->removeFollower($follower);
            $this->orm->remove($follower);
            $this->orm->flush();
        }
    }

    public function toggle(CompanyEstablishment $establishment, User $user): bool
    {
        if ($this->followers->isFollowing($establishment, $user)) {
            $this->unfollow($establishment, $user);

            return false;
        }

        $this->follow($establishment, $user);

        return true;
    }
}