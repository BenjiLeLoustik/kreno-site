<?php

namespace App\User\Service;

use App\Entity\User\User;
use App\Entity\User\UserAlert;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\User\Dto\AlertInput;
use App\User\Exception\AlertLabelRequiredException;
use App\User\Exception\AlertLimitReachedException;
use App\User\Exception\AlertTimeRangeException;
use App\User\Exception\CityNotOpenException;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class AlertService
{
    public const MAX_ALERTS = 5;

    private const COORDINATE_DECIMALS = 7;

    private const TIME_DATE = '1970-01-01 ';

    public function __construct(
        private OrmManagerInterface $orm,
        private CityRepository $cities,
        private CategoryRepository $categories,
        private TranslationManagerInterface $translation,
    ) {
    }

    public function create(User $user, AlertInput $input): UserAlert
    {
        if ($user->getAlerts()->count() >= self::MAX_ALERTS) {
            throw new AlertLimitReachedException(self::MAX_ALERTS);
        }

        $label = trim((string) $input->label);

        if ($label === '') {
            throw new AlertLabelRequiredException();
        }

        $alert = (new UserAlert())->setLabel($label);
        $this->apply($alert, $input);
        $user->addAlert($alert);

        $this->orm->persist($alert);
        $this->orm->flush();

        return $alert;
    }

    public function update(UserAlert $alert, AlertInput $input): UserAlert
    {
        $label = trim((string) $input->label);

        if ($label !== '') {
            $alert->setLabel($label);
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
            $this->applyCity($alert, $input->citySlug);
        }

        if ($input->latitude !== null && $input->longitude !== null) {
            $alert->setLatitude($this->coordinate($input->latitude));
            $alert->setLongitude($this->coordinate($input->longitude));
        }

        if ($input->radiusKm !== null) {
            $alert->setRadiusKm($input->radiusKm);
        }

        $this->applyTimes($alert, $input);

        if ($input->maxPrice !== null) {
            $alert->setMaxPrice($input->maxPrice ?: null);
        }

        if ($input->enabled !== null) {
            $alert->setEnabled($input->enabled);
        }

        if ($input->categorySlugs !== null) {
            $this->applyCategories($alert, $input->categorySlugs);
        }
    }

    private function applyCity(UserAlert $alert, string $slug): void
    {
        if ($slug === '') {
            $alert->setCity(null);

            return;
        }

        $city = $this->cities->findOneOpenBySlug($slug);

        if ($city === null) {
            throw new CityNotOpenException();
        }

        $alert->setCity($city);
    }

    private function applyTimes(UserAlert $alert, AlertInput $input): void
    {
        if ($input->startTime !== null) {
            $alert->setStartTime($this->time($input->startTime));
        }

        if ($input->endTime !== null) {
            $alert->setEndTime($this->time($input->endTime));
        }

        $start = $alert->getStartTime();
        $end = $alert->getEndTime();

        if ($start !== null && $end !== null && $end <= $start) {
            throw new AlertTimeRangeException();
        }
    }

    private function applyCategories(UserAlert $alert, array $slugs): void
    {
        foreach ($alert->getCategories()->toArray() as $category) {
            $alert->removeCategory($category);
        }

        foreach ($slugs as $slug) {
            $category = $this->categories->findOneVisibleBySlug(
                (string) $slug,
                $this->translation->getLocale()
            );

            if ($category !== null) {
                $alert->addCategory($category);
            }
        }
    }

    private function coordinate(float $value): string
    {
        return number_format($value, self::COORDINATE_DECIMALS, '.', '');
    }

    private function time(string $value): ?DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }

        return new DateTimeImmutable(self::TIME_DATE . $value);
    }
}