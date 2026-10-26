<?php

namespace App\Establishment\Provider;

use App\Catalog\Presenter\CategoryPresenter;
use App\Catalog\Presenter\CityPresenter;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\Establishment\EstablishmentClosure;
use App\Establishment\Exception\ClosureNotFoundException;
use App\Establishment\Presenter\EstablishmentPagePresenter;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\Establishment\EstablishmentClosureRepository;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class EstablishmentSettingsProvider
{
    public const RANGES_PER_DAY = 2;

    public function __construct(
        private PermissionResolver $resolver,
        private EstablishmentClosureRepository $closures,
        private CityRepository $cities,
        private CategoryRepository $categories,
        private EstablishmentPagePresenter $pagePresenter,
        private CityPresenter $cityPresenter,
        private CategoryPresenter $categoryPresenter,
        private TranslationManagerInterface $translation,
    ) {
    }

    public function show(CompanyMember $member, CompanyEstablishment $establishment): array
    {
        return [
            'establishment' => $this->pagePresenter->settings($establishment),
            'rights' => [
                'edit' => $this->resolver->memberCan($member, Permission::ESTABLISHMENT_EDIT, $establishment),
                'services' => $this->resolver->memberCan($member, Permission::SERVICES_MANAGE, $establishment),
                'team' => $this->resolver->memberCan($member, Permission::MEMBERS_MANAGE, $establishment),
            ],
            'categories' => $this->categoryPresenter->summaries($this->categories->findVisible($this->translation->getLocale())),
            'cities' => $this->cityPresenter->summaries($this->cities->findAvailableForPros()),
            'rangesPerDay' => self::RANGES_PER_DAY,
        ];
    }

    public function closure(CompanyEstablishment $establishment, int $id): EstablishmentClosure
    {
        $closure = $this->closures->findOneInEstablishment($establishment, $id);

        if ($closure === null) {
            throw new ClosureNotFoundException();
        }

        return $closure;
    }
}