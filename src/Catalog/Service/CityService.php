<?php

namespace App\Catalog\Service;

use App\Catalog\Dto\CityInput;
use App\Catalog\Exception\CityNameRequiredException;
use App\Catalog\Exception\CityNotEmptyException;
use App\Entity\City;
use App\Entity\User\User;
use App\Enum\City\CityStatus;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Repository\CityRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;

class CityService
{
    public const COORDINATE_DECIMALS = 7;

    public function __construct(
        private OrmManagerInterface $orm,
        private CityRepository $cities,
        private CompanyEstablishmentRepository $establishments,
        private ActivityLogger $logger,
    ) {
    }

    public function create(CityInput $input, ?User $actor = null): City
    {
        $name = $this->name($input->name) ?? throw new CityNameRequiredException();

        $city = (new City())
            ->setName($name)
            ->setPosition($input->position ?? $this->cities->nextPosition());

        $this->apply($city, $input);
        $this->changeStatus($city, $input->status ?? CityStatus::WAITLIST);
        $this->orm->persist($city);

        $message = sprintf(
            'Ville %s ajoutée (%s)',
            $city->getName(),
            mb_strtolower($city->getStatus()->label())
        );
        $this->logger->log(ActivityType::CATALOG, $message, $actor, null, $city);
        $this->orm->flush();

        return $city;
    }

    public function update(City $city, CityInput $input, ?User $actor = null): City
    {
        $name = $this->name($input->name);

        if ($name !== null) {
            $city->setName($name);
        }

        $this->apply($city, $input);

        if ($input->status !== null && $input->status !== $city->getStatus()) {
            return $this->updateStatus($city, $input->status, $actor);
        }

        $this->logger->log(
            ActivityType::CATALOG,
            sprintf('Ville %s modifiée', $city->getName()),
            $actor,
            null,
            $city
        );
        $this->orm->flush();

        return $city;
    }

    public function updateStatus(City $city, CityStatus $status, ?User $actor = null): City
    {
        $previous = $city->getStatus();

        if ($status === $previous) {
            return $city;
        }

        $this->changeStatus($city, $status);

        $message = sprintf(
            '%s : %s -> %s',
            $city->getName(),
            mb_strtolower($previous->label()),
            mb_strtolower($status->label()),
        );
        $context = [
            'status' => [
                'from' => $previous->value,
                'to' => $status->value
            ]
        ];

        $this->logger->log(ActivityType::CATALOG, $message, $actor, null, $city, $context);
        $this->orm->flush();

        return $city;
    }

    public function delete(City $city, ?User $actor = null): void
    {
        if ($this->establishments->countByCity($city) > 0) {
            throw new CityNotEmptyException((string) $city->getName());
        }

        $this->logger->log(
            ActivityType::CATALOG,
            sprintf('Ville %s supprimée', $city->getName()),
            $actor
        );
        $this->orm->remove($city);
        $this->orm->flush();
    }

    public function reorder(array $slugs, ?User $actor = null): void
    {
        foreach (array_values($slugs) as $position => $slug) {
            $this->cities->findOneBySlug($slug)?->setPosition($position);
        }

        $this->logger->log(
            ActivityType::CATALOG,
            'Ordre des villes modifié',
            $actor
        );
        $this->orm->flush();
    }

    private function name(?string $name): ?string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return $name;
    }

    private function apply(City $city, CityInput $input): void
    {
        if ($input->departmentCode !== null) {
            $city->setDepartmentCode($input->departmentCode ?: null);
        }

        if ($input->latitude !== null) {
            $city->setLatitude($this->coordinate($input->latitude));
        }

        if ($input->longitude !== null) {
            $city->setLongitude($this->coordinate($input->longitude));
        }

        if ($input->radiusKm !== null) {
            $city->setRadiusKm($input->radiusKm);
        }

        if ($input->position !== null) {
            $city->setPosition($input->position);
        }
    }

    private function coordinate(float $value): string
    {
        return number_format($value, self::COORDINATE_DECIMALS, '.', '');
    }

    private function changeStatus(City $city, CityStatus $status): void
    {
        $city->setStatus($status);

        if ($status === CityStatus::OPEN && $city->getOpenedAt() === null) {
            $city->setOpenedAt(new DateTimeImmutable());
        }
    }
}