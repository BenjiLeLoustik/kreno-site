<?php

namespace App\Establishment\Provider;

use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\Establishment\EstablishmentService;
use App\Establishment\Exception\ServiceNotFoundException;
use App\Establishment\Presenter\ServicePresenter;
use App\Repository\Establishment\EstablishmentServiceRepository;

class ServicePageProvider
{
    public const DURATIONS = [15, 20, 30, 45, 60, 75, 90, 120, 150, 180, 240];

    public function __construct(
        private PermissionResolver $resolver,
        private EstablishmentServiceRepository $services,
        private ServicePresenter $presenter,
    ) {
    }

    public function index(CompanyMember $member, CompanyEstablishment $establishment): array
    {
        return [
            'services' => $this->presenter->summaries($this->services->findForEstablishment($establishment)),
            'canManage' => $this->resolver->memberCan($member, Permission::SERVICES_MANAGE, $establishment),
            'defaultDiscount' => $establishment->getDefaultDiscount(),
            'durations' => self::DURATIONS,
        ];
    }

    public function owned(CompanyEstablishment $establishment, int $id): EstablishmentService
    {
        $service = $this->services->findOneInEstablishment($establishment, $id);

        if ($service === null) {
            throw new ServiceNotFoundException();
        }

        return $service;
    }
}