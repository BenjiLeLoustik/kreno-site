<?php

namespace App\Controller\Pro;

use App\Catalog\Presenter\CategoryPresenter;
use App\Catalog\Presenter\CityPresenter;
use App\Company\Dto\CompanyInput;
use App\Company\Dto\EstablishmentInput;
use App\Company\Presenter\CompanyPresenter;
use App\Company\Presenter\EstablishmentSummaryPresenter;
use App\Company\Presenter\TeamPresenter;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Company\Service\CompanyService;
use App\Company\Service\EstablishmentRegistrationService;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class CompanyController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        private CompanyService $companies,
        private EstablishmentRegistrationService $establishments,
        private CityRepository $cities,
        private CategoryRepository $categories,
        private CompanyPresenter $companyPresenter,
        private EstablishmentSummaryPresenter $establishmentPresenter,
        private TeamPresenter $teamPresenter,
        private CityPresenter $cityPresenter,
        private CategoryPresenter $categoryPresenter,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Route('/pro/company', name: 'pro_company', methods: ['GET'])]
    public function show(): Response
    {
        $member = $this->currentMember();
        $company = $this->currentCompany();

        return $this->render('pro/company/index.html.twig', [
            'context' => $this->proContext(),
            'company' => $this->companyPresenter->detail($company),
            'canEdit' => $this->resolver->memberCan($member, Permission::COMPANY_EDIT, $company),
            'canCreate' => $this->resolver->memberCan($member, Permission::ESTABLISHMENT_CREATE, $company),
            'establishments' => array_map(fn (CompanyEstablishment $establishment): array => $this->establishmentPresenter->forCompany($establishment), $company->getEstablishments()->toArray()),
            'administrators' => array_map(
                fn (CompanyMember $admin): array => $this->teamPresenter->member($admin, $member),
                array_values(array_filter($company->getMembers()->toArray(), static fn (CompanyMember $candidate): bool => $candidate->getRole() !== null)),
            ),
            'managers' => array_map(fn (CompanyMember $candidate): array => ['id' => $candidate->getId(), 'name' => $this->teamPresenter->member($candidate)['name']], $company->getMembers()->toArray()),
            'cities' => $this->cityPresenter->summaries($this->cities->findAvailableForPros()),
            'categories' => $this->categoryPresenter->summaries($this->categories->findVisible()),
        ]);
    }

    #[Csrf('pro')]
    #[Route('/pro/company', name: 'pro_company_update', methods: ['POST'])]
    public function update(Request $request, #[MapRequestPayload(validate: false)] CompanyInput $input): RedirectResponse
    {
        $company = $this->currentCompany();
        $this->requirePermission(Permission::COMPANY_EDIT, $company);
        $input->bookingEmailNotifications = $request->request->getBoolean('bookingEmailNotifications');
        $input->monthlyTips = $request->request->getBoolean('monthlyTips');

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->companies->update($company, $input), 'Informations de l\'entreprise enregistrées.');
        }

        return $this->redirectToRoute('pro_company');
    }

    #[Csrf('pro')]
    #[Route('/pro/company/establishments', name: 'pro_company_establishment_create', methods: ['POST'])]
    public function createEstablishment(#[MapRequestPayload(validate: false)] EstablishmentInput $input): RedirectResponse
    {
        $company = $this->currentCompany();
        $this->requirePermission(Permission::ESTABLISHMENT_CREATE, $company);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->establishments->create($company, $input), sprintf('%s est créé en brouillon. Complétez-le puis envoyez-le en vérification.', $input->name));
        }

        return $this->redirectToRoute('pro_company');
    }

    #[Csrf('pro')]
    #[Route('/pro/company/establishments/{id}/submit', name: 'pro_company_establishment_submit', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function submitEstablishment(int $id): RedirectResponse
    {
        $company = $this->currentCompany();
        $this->requirePermission(Permission::ESTABLISHMENT_CREATE, $company);
        $establishment = null;

        foreach ($company->getEstablishments() as $candidate) {
            if ($candidate->getId() === $id) {
                $establishment = $candidate;
            }
        }

        $establishment ??= throw $this->createNotFoundException('Établissement introuvable.');
        $this->attempt(fn () => $this->establishments->submit($establishment), sprintf('%s est envoyé à l\'équipe Kréno pour vérification.', $establishment->getName()));

        return $this->redirectToRoute('pro_company');
    }
}