<?php

namespace App\Controller\Pro;

use App\Company\Dto\EstablishmentInput;
use App\Company\Mapper\CompanyRequestMapper;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Company\Service\EstablishmentRegistrationService;
use App\Establishment\Dto\ClosureInput;
use App\Establishment\Enum\EstablishmentFlash;
use App\Establishment\Mapper\OpeningHoursRequestMapper;
use App\Establishment\Provider\EstablishmentSettingsProvider;
use App\Establishment\Service\ClosureService;
use App\Establishment\Service\OpeningHoursService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class EstablishmentController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private EstablishmentRegistrationService $registration,
        private OpeningHoursService $openingHours,
        private ClosureService $closureService,
        private EstablishmentSettingsProvider $pages,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route('/pro/establishment', name: 'pro_establishment', methods: ['GET'])]
    public function show(): Response
    {
        $member = $this->currentMember();
        $establishment = $this->permittedEstablishment(Permission::PLANNING_VIEW);

        return $this->renderPro('pro/establishment/index.html.twig', $this->pages->show($member, $establishment));
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment', name: 'pro_establishment_update', methods: ['POST'])]
    public function update(#[MapRequestPayload(validate: false)] EstablishmentInput $input): RedirectResponse
    {
        $establishment = $this->permittedEstablishment(Permission::ESTABLISHMENT_EDIT);
        $input = CompanyRequestMapper::establishmentSettings($input);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->registration->update($establishment, $input),
                EstablishmentFlash::UPDATED->text(),
            );
        }

        return $this->redirectToRoute('pro_establishment');
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/hours', name: 'pro_establishment_hours', methods: ['POST'])]
    public function hours(Request $request): RedirectResponse
    {
        $establishment = $this->permittedEstablishment(Permission::ESTABLISHMENT_EDIT);
        $input = OpeningHoursRequestMapper::openingHours($request);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->openingHours->replace($establishment, $input->hours),
                EstablishmentFlash::HOURS_SAVED->text(),
            );
        }

        return $this->redirect($this->generateUrl('pro_establishment') . '#horaires');
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/closures', name: 'pro_establishment_closure_create', methods: ['POST'])]
    public function createClosure(#[MapRequestPayload(validate: false)] ClosureInput $input): RedirectResponse
    {
        $establishment = $this->permittedEstablishment(Permission::ESTABLISHMENT_EDIT);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->closureService->create($establishment, $input),
                EstablishmentFlash::CLOSURE_ADDED->text(),
            );
        }

        return $this->redirect($this->generateUrl('pro_establishment') . '#horaires');
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/establishment/closures/{id}/delete',
        name: 'pro_establishment_closure_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function deleteClosure(int $id): RedirectResponse
    {
        $closure = $this->pages->closure($this->permittedEstablishment(Permission::ESTABLISHMENT_EDIT), $id);
        $this->attempt(fn () => $this->closureService->delete($closure), EstablishmentFlash::CLOSURE_DELETED->text());

        return $this->redirect($this->generateUrl('pro_establishment') . '#horaires');
    }
}