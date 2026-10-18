<?php

namespace App\Company\Provider;

use App\Catalog\Presenter\CategoryPresenter;
use App\Catalog\Presenter\CityPresenter;
use App\Company\Presenter\CompanyPresenter;
use App\Company\Presenter\EstablishmentSummaryPresenter;
use App\Company\Presenter\TeamPresenter;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Establishment\Exception\EstablishmentNotFoundException;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;

class CompanyPageProvider
{
    public function __construct(
        private PermissionResolver $resolver,
        private CityRepository $cities,
        private CategoryRepository $categories,
        private CompanyPresenter $companyPresenter,
        private EstablishmentSummaryPresenter $establishmentPresenter,
        private TeamPresenter $teamPresenter,
        private CityPresenter $cityPresenter,
        private CategoryPresenter $categoryPresenter,
    ) {
    }

    public function show(CompanyMember $member, Company $company): array
    {
        return [
            'company' => $this->companyPresenter->detail($company),
            'canEdit' => $this->resolver->memberCan($member, Permission::COMPANY_EDIT, $company),
            'canCreate' => $this->resolver->memberCan($member, Permission::ESTABLISHMENT_CREATE, $company),
            'establishments' => $this->establishments($company),
            'administrators' => $this->administrators($member, $company),
            'managers' => $this->managers($company),
            'cities' => $this->cityPresenter->summaries($this->cities->findAvailableForPros()),
            'categories' => $this->categoryPresenter->summaries($this->categories->findVisible()),
        ];
    }

    public function establishment(Company $company, int $id): CompanyEstablishment
    {
        foreach ($company->getEstablishments() as $establishment) {
            if ($establishment->getId() === $id) {
                return $establishment;
            }
        }

        throw new EstablishmentNotFoundException();
    }

    private function establishments(Company $company): array
    {
        $establishments = [];

        foreach ($company->getEstablishments() as $establishment) {
            $establishments[] = $this->establishmentPresenter->forCompany($establishment);
        }

        return $establishments;
    }

    private function administrators(CompanyMember $viewer, Company $company): array
    {
        $administrators = [];

        foreach ($company->getMembers() as $member) {
            if ($member->getRole() !== null) {
                $administrators[] = $this->teamPresenter->member($member, $viewer);
            }
        }

        return $administrators;
    }

    private function managers(Company $company): array
    {
        $managers = [];

        foreach ($company->getMembers() as $member) {
            $managers[] = [
                'id' => $member->getId(),
                'name' => $this->teamPresenter->member($member)['name']
            ];
        }

        return $managers;
    }
}