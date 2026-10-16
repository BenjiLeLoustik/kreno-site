<?php

namespace App\Catalog\Service;

use App\Catalog\Dto\CityInput;
use App\Entity\City;
use App\Entity\User\User;
use App\Enum\City\CityStatus;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Repository\CityRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Shared\Exception\BusinessRuleException;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;

class CityService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private CityRepository $cities,
        private CompanyEstablishmentRepository $establishments,
        private ActivityLogger $logger,
    ) {
    }

    public function create(CityInput $input, ?User $actor = null): City
    {
        if ($input->name === null || trim($input->name) === '') {
            throw new BusinessRuleException('Le nom de la ville est obligatoire.');
        }

        $city = (new City())
            ->setName(trim($input->name))
            ->setPosition($input->position ?? $this->cities->nextPosition());

        $this->apply($city, $input);
        $this->changeStatus($city, $input->status ?? CityStatus::WAITLIST);

        $this->orm->persist($city);
        $this->logger->log(ActivityType::CATALOG, sprintf('Ville %s ajoutée (%s)', $city->getName(), mb_strtolower($city->getStatus()->label())), $actor, null, $city);
        $this->orm->flush();

        return $city;
    }

    public function update(City $city, CityInput $input, ?User $actor = null): City
    {
        if ($input->name !== null && trim($input->name) !== '') {
            $city->setName(trim($input->name));
        }

        $this->apply($city, $input);

        if ($input->status !== null && $input->status !== $city->getStatus()) {
            $this->updateStatus($city, $input->status, $actor);

            return $city;
        }

        $this->logger->log(ActivityType::CATALOG, sprintf('Ville %s modifiée', $city->getName()), $actor, null, $city);
        $this->orm->flush();

        return $city;
    }

    public function updateStatus(City $city, CityStatus $status, ?User $actor = null): City
    {
        if ($status === $city->getStatus()) {
            return $city;
        }

        $previous = $city->getStatus();
        $this->changeStatus($city, $status);

        $this->logger->log(ActivityType::CATALOG, sprintf('%s : %s → %s', $city->getName(), mb_strtolower($previous->label()), mb_strtolower($status->label())), $actor, null, $city, ['status' => ['from' => $previous->value, 'to' => $status->value]]);
        $this->orm->flush();

        return $city;
    }

    public function delete(City $city, ?User $actor = null): void
    {
        if ($this->establishments->countByCity($city) > 0) {
            throw new BusinessRuleException('{city} a des établissements : fermez-la plutôt que de la supprimer.', ['city' => $city->getName()]);
        }

        $this->logger->log(ActivityType::CATALOG, sprintf('Ville %s supprimée', $city->getName()), $actor);
        $this->orm->remove($city);
        $this->orm->flush();
    }

    public function reorder(array $slugs, ?User $actor = null): void
    {
        foreach (array_values($slugs) as $position => $slug) {
            $this->cities->findOneBySlug($slug)?->setPosition($position);
        }

        $this->logger->log(ActivityType::CATALOG, 'Ordre des villes modifié', $actor);
        $this->orm->flush();
    }

    private function apply(City $city, CityInput $input): void
    {
        if ($input->departmentCode !== null) {
            $city->setDepartmentCode($input->departmentCode ?: null);
        }

        if ($input->latitude !== null) {
            $city->setLatitude(number_format($input->latitude, 7, '.', ''));
        }

        if ($input->longitude !== null) {
            $city->setLongitude(number_format($input->longitude, 7, '.', ''));
        }

        if ($input->radiusKm !== null) {
            $city->setRadiusKm($input->radiusKm);
        }

        if ($input->position !== null) {
            $city->setPosition($input->position);
        }
    }

    private function changeStatus(City $city, CityStatus $status): void
    {
        $city->setStatus($status);

        if ($status === CityStatus::OPEN && $city->getOpenedAt() === null) {
            $city->setOpenedAt(new DateTimeImmutable());
        }
    }
}