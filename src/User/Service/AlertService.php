<?php

namespace App\User\Service;

use App\Entity\User\User;
use App\Entity\User\UserAlert;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Shared\Exception\BusinessRuleException;
use App\User\Dto\AlertInput;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;

class AlertService
{
    private const MAX_ALERTS = 5;

    public function __construct(
        private OrmManagerInterface $orm,
        private CityRepository $cities,
        private CategoryRepository $categories,
    ) {
    }

    public function create(User $user, AlertInput $input): UserAlert
    {
        if ($user->getAlerts()->count() >= self::MAX_ALERTS) {
            throw new BusinessRuleException('Vous avez déjà {max} alertes : supprimez-en une pour en créer une nouvelle.', ['max' => self::MAX_ALERTS]);
        }

        if ($input->label === null || trim($input->label) === '') {
            throw new BusinessRuleException('Indiquez le quartier ou l\'adresse de l\'alerte.');
        }

        $alert = (new UserAlert())->setLabel(trim($input->label));
        $this->apply($alert, $input);
        $user->addAlert($alert);

        $this->orm->persist($alert);
        $this->orm->flush();

        return $alert;
    }

    public function update(UserAlert $alert, AlertInput $input): UserAlert
    {
        if ($input->label !== null && trim($input->label) !== '') {
            $alert->setLabel(trim($input->label));
        }

        $this->apply($alert, $input);
        $this->orm->flush();

        return $alert;
    }

    public function delete(UserAlert $alert): void
    {
        $alert->getUser()?->removeAlert($alert);
        $this->orm->remove($alert);
        $this->orm->flush();
    }

    private function apply(UserAlert $alert, AlertInput $input): void
    {
        if ($input->citySlug !== null) {
            $city = $input->citySlug === '' ? null : $this->cities->findOneOpenBySlug($input->citySlug);

            if ($input->citySlug !== '' && $city === null) {
                throw new BusinessRuleException('Cette ville n\'est pas encore ouverte sur Kréno.');
            }

            $alert->setCity($city);
        }

        if ($input->latitude !== null && $input->longitude !== null) {
            $alert->setLatitude(number_format($input->latitude, 7, '.', ''));
            $alert->setLongitude(number_format($input->longitude, 7, '.', ''));
        }

        if ($input->radiusKm !== null) {
            $alert->setRadiusKm($input->radiusKm);
        }

        if ($input->startTime !== null) {
            $alert->setStartTime($input->startTime === '' ? null : new DateTimeImmutable('1970-01-01 ' . $input->startTime));
        }

        if ($input->endTime !== null) {
            $alert->setEndTime($input->endTime === '' ? null : new DateTimeImmutable('1970-01-01 ' . $input->endTime));
        }

        if ($alert->getStartTime() !== null && $alert->getEndTime() !== null && $alert->getEndTime() <= $alert->getStartTime()) {
            throw new BusinessRuleException('L\'heure de fin doit être après l\'heure de début.');
        }

        if ($input->maxPrice !== null) {
            $alert->setMaxPrice($input->maxPrice ?: null);
        }

        if ($input->enabled !== null) {
            $alert->setEnabled($input->enabled);
        }

        if ($input->categorySlugs !== null) {
            foreach ($alert->getCategories()->toArray() as $category) {
                $alert->removeCategory($category);
            }

            foreach ($input->categorySlugs as $slug) {
                $category = $this->categories->findOneVisibleBySlug((string) $slug);

                if ($category !== null) {
                    $alert->addCategory($category);
                }
            }
        }
    }
}