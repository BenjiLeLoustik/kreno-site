<?php

namespace App\Establishment\Service;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentService;
use App\Establishment\Dto\ServiceInput;
use App\Establishment\Dto\ServicesInput;
use App\Repository\Establishment\EstablishmentServiceRepository;
use App\Repository\Establishment\EstablishmentSlotRepository;
use App\Shared\Exception\BusinessRuleException;
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

    public function create(CompanyEstablishment $establishment, ServiceInput $input, bool $flush = true): EstablishmentService
    {
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
            $existing = [];

            foreach ($this->services->findForEstablishment($establishment) as $service) {
                $existing[mb_strtolower((string) $service->getName())] = $service;
            }

            $kept = [];

            foreach (array_values($input->services) as $position => $serviceInput) {
                $key = mb_strtolower(trim((string) $serviceInput->name));
                $service = $existing[$key] ?? null;
                unset($existing[$key]);

                if ($service === null) {
                    $service = $this->create($establishment, $serviceInput, false);
                } else {
                    $this->apply($service, $serviceInput);
                    $service->setActive(true);
                }

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

    private function apply(EstablishmentService $service, ServiceInput $input): void
    {
        if ($input->defaultDiscount !== null) {
            $this->assertDiscount($input->defaultDiscount);
        }

        $description = trim((string) $input->description);

        $service->setName(trim((string) $input->name));
        $service->setDescription($description !== '' ? $description : null);
        $service->setPrice((int) $input->price);
        $service->setDuration((int) $input->duration);
        $service->setDefaultDiscount($input->defaultDiscount);

        if ($input->active !== null) {
            $service->setActive($input->active);
        }
    }

    private function assertDiscount(int $discount): void
    {
        $rule = $this->website->getRule();

        if ($discount < $rule->getMinDiscount() || $discount > $rule->getMaxDiscount()) {
            throw new BusinessRuleException('La remise doit être comprise entre {min} et {max} %.', ['min' => $rule->getMinDiscount(), 'max' => $rule->getMaxDiscount()]);
        }
    }
}