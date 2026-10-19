<?php

namespace App\Establishment\Service;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentService;
use App\Establishment\Dto\ServiceInput;
use App\Establishment\Dto\ServicesInput;
use App\Establishment\Exception\DiscountOutOfRangeException;
use App\Repository\Establishment\EstablishmentServiceRepository;
use App\Repository\Establishment\EstablishmentSlotRepository;
use App\Website\Provider\WebsiteProvider;
use NeoPHP\Package\Orm\OrmManagerInterface;

class ServiceCatalogService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private EstablishmentServiceRepository $services,
        private EstablishmentSlotRepository $slots,
        private WebsiteProvider $website,
    ) {
    }

    public function create(
        CompanyEstablishment $establishment,
        ServiceInput $input,
        bool $flush = true,
    ): EstablishmentService {
        $service = new EstablishmentService();
        $service->setPosition($this->services->nextPosition($establishment));
        $this->apply($service, $input);
        $establishment->addService($service);
        $this->orm->persist($service);

        if ($flush) {
            $this->orm->flush();
        }

        return $service;
    }

    public function update(EstablishmentService $service, ServiceInput $input): EstablishmentService
    {
        $this->apply($service, $input);
        $this->orm->flush();

        return $service;
    }

    public function setActive(EstablishmentService $service, bool $active): EstablishmentService
    {
        $service->setActive($active);
        $this->orm->flush();

        return $service;
    }

    public function delete(EstablishmentService $service, bool $flush = true): bool
    {
        $removed = $this->slots->count(['service' => $service]) === 0;

        if ($removed) {
            $service->getEstablishment()?->removeService($service);
            $this->orm->remove($service);
        } else {
            $service->setActive(false);
        }

        if ($flush) {
            $this->orm->flush();
        }

        return $removed;
    }

    public function reorder(CompanyEstablishment $establishment, array $ids): void
    {
        foreach (array_values($ids) as $position => $id) {
            $this->services->findOneInEstablishment($establishment, (int) $id)?->setPosition($position);
        }

        $this->orm->flush();
    }

    public function replaceAll(CompanyEstablishment $establishment, ServicesInput $input): array
    {
        return $this->orm->transactional(function () use ($establishment, $input): array {
            $existing = $this->indexByName($establishment);
            $kept = [];

            foreach (array_values($input->services) as $position => $serviceInput) {
                $key = $this->key($serviceInput->name);
                $service = $this->keep($establishment, $existing[$key] ?? null, $serviceInput);
                unset($existing[$key]);

                $service->setPosition($position);
                $kept[] = $service;
            }

            foreach ($existing as $service) {
                $this->delete($service, false);
            }

            if ($input->defaultDiscount !== null) {
                $this->assertDiscount($input->defaultDiscount);
                $establishment->setDefaultDiscount($input->defaultDiscount);
            }

            $this->orm->flush();

            return $kept;
        });
    }

    private function indexByName(CompanyEstablishment $establishment): array
    {
        $services = [];

        foreach ($this->services->findForEstablishment($establishment) as $service) {
            $services[$this->key($service->getName())] = $service;
        }

        return $services;
    }

    private function key(?string $name): string
    {
        return mb_strtolower(trim((string) $name));
    }

    private function keep(
        CompanyEstablishment $establishment,
        ?EstablishmentService $service,
        ServiceInput $input,
    ): EstablishmentService {
        if ($service === null) {
            return $this->create($establishment, $input, false);
        }

        $this->apply($service, $input);
        $service->setActive(true);

        return $service;
    }

    private function apply(EstablishmentService $service, ServiceInput $input): void
    {
        if ($input->defaultDiscount !== null) {
            $this->assertDiscount($input->defaultDiscount);
        }

        $service->setName(trim((string) $input->name));
        $service->setDescription($this->description($input->description));
        $service->setPrice((int) $input->price);
        $service->setDuration((int) $input->duration);
        $service->setDefaultDiscount($input->defaultDiscount);

        if ($input->active !== null) {
            $service->setActive($input->active);
        }
    }

    private function description(?string $description): ?string
    {
        $description = trim((string) $description);

        if ($description === '') {
            return null;
        }

        return $description;
    }

    private function assertDiscount(int $discount): void
    {
        $rule = $this->website->getRule();

        if ($discount < $rule->getMinDiscount() || $discount > $rule->getMaxDiscount()) {
            throw new DiscountOutOfRangeException($rule->getMinDiscount(), $rule->getMaxDiscount());
        }
    }
}