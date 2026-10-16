<?php

namespace App\Repository\User;

use App\Entity\City;
use App\Entity\User\User;
use App\Entity\User\UserAlert;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class UserAlertRepository extends AbstractRepository
{
    protected string $entityClass = UserAlert::class;

    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'ASC']);
    }

    public function findEnabledInCity(City $city): array
    {
        return $this->findBy(['city' => $city, 'enabled' => true]);
    }
}