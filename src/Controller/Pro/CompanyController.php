<?php

namespace App\Controller\Pro;

use App\Company\Dto\CompanyInput;
use App\Company\Dto\EstablishmentInput;
use App\Company\Enum\CompanyFlash;
use App\Company\Mapper\CompanyRequestMapper;
use App\Company\Provider\CompanyPageProvider;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Company\Service\CompanyService;
use App\Company\Service\EstablishmentRegistrationService;
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
        ProContextProvider $pro,
        private CompanyService $companies,
        private EstablishmentRegistrationService $establishments,
        private CompanyPageProvider $pages,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route('/pro/company', name: 'pro_company', methods: ['GET'])]
    public function show(): Response
    {
        $member = $this->currentMember();

        return $this->renderPro('pro/company/index.html.twig', $this->pages->show($member, $this->currentCompany()));
    }

    #[Csrf('pro')]
    #[Route('/pro/company', name: 'pro_company_update', methods: ['POST'])]
    public function update(
        Request $request,
        #[MapRequestPayload(validate: false)] CompanyInput $input,
    ): RedirectResponse {
        $company = $this->permittedCompany(Permission::COMPANY_EDIT);
        $input = CompanyRequestMapper::company($input, $request);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->companies->update($company, $input), CompanyFlash::UPDATED->text());
        }

        return $this->redirectToRoute('pro_company');
    }

    #[Csrf('pro')]
    #[Route('/pro/company/establishments', name: 'pro_company_establishment_create', methods: ['POST'])]
    public function createEstablishment(
        #[MapRequestPayload(validate: false)] EstablishmentInput $input,
    ): RedirectResponse {
        $company = $this->permittedCompany(Permission::ESTABLISHMENT_CREATE);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->establishments->create($company, $input),
                CompanyFlash::ESTABLISHMENT_CREATED->text($input->name),
            );
        }

        return $this->redirectToRoute('pro_company');
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/company/establishments/{id}/submit',
        name: 'pro_company_establishment_submit',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function submitEstablishment(int $id): RedirectResponse
    {
        $company = $this->permittedCompany(Permission::ESTABLISHMENT_CREATE);
        $establishment = $this->pages->establishment($company, $id);
        $this->attempt(
            fn () => $this->establishments->submit($establishment),
            CompanyFlash::ESTABLISHMENT_SUBMITTED->text($establishment->getName()),
        );

        return $this->redirectToRoute('pro_company');
    }
}