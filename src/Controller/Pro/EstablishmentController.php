<?php

namespace App\Controller\Pro;

use App\Catalog\Presenter\CategoryPresenter;
use App\Catalog\Presenter\CityPresenter;
use App\Company\Dto\EstablishmentInput;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Company\Service\EstablishmentRegistrationService;
use App\Establishment\Dto\ClosureInput;
use App\Establishment\Dto\OpeningHourInput;
use App\Establishment\Dto\OpeningHoursInput;
use App\Establishment\Presenter\EstablishmentPagePresenter;
use App\Establishment\Service\ClosureService;
use App\Establishment\Service\OpeningHoursService;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\Establishment\EstablishmentClosureRepository;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class EstablishmentController extends AbstractProController
{
    public const RANGES_PER_DAY = 2;

    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        private EstablishmentRegistrationService $registration,
        private OpeningHoursService $openingHours,
        private ClosureService $closureService,
        private EstablishmentClosureRepository $closures,
        private CityRepository $cities,
        private CategoryRepository $categories,
        private EstablishmentPagePresenter $pagePresenter,
        private CityPresenter $cityPresenter,
        private CategoryPresenter $categoryPresenter,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Route('/pro/establishment', name: 'pro_establishment', methods: ['GET'])]
    public function show(): Response
    {
        $member = $this->currentMember();
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::PLANNING_VIEW, $establishment);

        return $this->render('pro/establishment/index.html.twig', [
            'context' => $this->proContext(),
            'establishment' => $this->pagePresenter->settings($establishment),
            'rights' => [
                'edit' => $this->resolver->memberCan($member, Permission::ESTABLISHMENT_EDIT, $establishment),
                'services' => $this->resolver->memberCan($member, Permission::SERVICES_MANAGE, $establishment),
                'team' => $this->resolver->memberCan($member, Permission::MEMBERS_MANAGE, $establishment),
            ],
            'categories' => $this->categoryPresenter->summaries($this->categories->findVisible()),
            'cities' => $this->cityPresenter->summaries($this->cities->findAvailableForPros()),
            'rangesPerDay' => self::RANGES_PER_DAY,
        ]);
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment', name: 'pro_establishment_update', methods: ['POST'])]
    public function update(#[MapRequestPayload(validate: false)] EstablishmentInput $input): RedirectResponse
    {
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::ESTABLISHMENT_EDIT, $establishment);
        $input->managerMemberId = null;
        $input->siret = null;

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->registration->update($establishment, $input), 'Informations enregistrées.');
        }

        return $this->redirectToRoute('pro_establishment');
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/hours', name: 'pro_establishment_hours', methods: ['POST'])]
    public function hours(Request $request): RedirectResponse
    {
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::ESTABLISHMENT_EDIT, $establishment);
        $input = new OpeningHoursInput();
        $rows = $request->request->get('hours');

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row) || ($row['opensAt'] ?? '') === '' || ($row['closesAt'] ?? '') === '') {
                continue;
            }

            $hour = new OpeningHourInput();
            $hour->weekday = (int) ($row['weekday'] ?? 0);
            $hour->opensAt = (string) $row['opensAt'];
            $hour->closesAt = (string) $row['closesAt'];
            $input->hours[] = $hour;
        }

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->openingHours->replace($establishment, $input->hours), 'Horaires enregistrés.');
        }

        return $this->redirect($this->generateUrl('pro_establishment') . '#horaires');
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/closures', name: 'pro_establishment_closure_create', methods: ['POST'])]
    public function createClosure(#[MapRequestPayload(validate: false)] ClosureInput $input): RedirectResponse
    {
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::ESTABLISHMENT_EDIT, $establishment);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->closureService->create($establishment, $input), 'Fermeture exceptionnelle ajoutée.');
        }

        return $this->redirect($this->generateUrl('pro_establishment') . '#horaires');
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/closures/{id}/delete', name: 'pro_establishment_closure_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteClosure(int $id): RedirectResponse
    {
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::ESTABLISHMENT_EDIT, $establishment);
        $closure = $this->closures->findOneInEstablishment($establishment, $id) ?? throw $this->createNotFoundException('Fermeture introuvable.');
        $this->attempt(fn () => $this->closureService->delete($closure), 'Fermeture supprimée.');

        return $this->redirect($this->generateUrl('pro_establishment') . '#horaires');
    }
}