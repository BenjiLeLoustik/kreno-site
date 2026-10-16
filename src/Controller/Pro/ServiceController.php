<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Establishment\EstablishmentService;
use App\Establishment\Dto\OrderInput;
use App\Establishment\Dto\ServiceInput;
use App\Establishment\Presenter\ServicePresenter;
use App\Establishment\Service\ServiceCatalogService;
use App\Repository\Establishment\EstablishmentServiceRepository;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class ServiceController extends AbstractProController
{
    public const DURATIONS = [15, 20, 30, 45, 60, 75, 90, 120, 150, 180, 240];

    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        private ServiceCatalogService $catalog,
        private EstablishmentServiceRepository $services,
        private ServicePresenter $presenter,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Route('/pro/establishment/services', name: 'pro_services', methods: ['GET'])]
    public function index(): Response
    {
        $member = $this->currentMember();
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::PLANNING_VIEW, $establishment);

        return $this->render('pro/service/index.html.twig', [
            'context' => $this->proContext(),
            'services' => $this->presenter->summaries($this->services->findForEstablishment($establishment)),
            'canManage' => $this->resolver->memberCan($member, Permission::SERVICES_MANAGE, $establishment),
            'defaultDiscount' => $establishment->getDefaultDiscount(),
            'durations' => self::DURATIONS,
        ]);
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/services', name: 'pro_service_create', methods: ['POST'])]
    public function create(Request $request): RedirectResponse
    {
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::SERVICES_MANAGE, $establishment);
        $input = $this->input($request);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->catalog->create($establishment, $input), sprintf('Prestation « %s » ajoutée.', $input->name));
        }

        return $this->redirectToRoute('pro_services');
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/services/{id}', name: 'pro_service_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(Request $request, int $id): RedirectResponse
    {
        $service = $this->findService($id);
        $input = $this->input($request);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->catalog->update($service, $input), sprintf('Prestation « %s » modifiée.', $input->name));
        }

        return $this->redirectToRoute('pro_services');
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/services/{id}/toggle', name: 'pro_service_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(int $id): RedirectResponse
    {
        $service = $this->findService($id);
        $active = !$service->isActive();
        $this->attempt(fn () => $this->catalog->setActive($service, $active), sprintf('« %s » est %s.', $service->getName(), $active ? 'de nouveau proposée' : 'désactivée'));

        return $this->redirectToRoute('pro_services');
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/services/{id}/delete', name: 'pro_service_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id): RedirectResponse
    {
        $service = $this->findService($id);
        $deleted = null;
        $this->attempt(function () use ($service, &$deleted): void {
            $deleted = $this->catalog->delete($service);
        });

        if ($deleted !== null) {
            $this->addFlash('success', $deleted ? 'Prestation supprimée.' : 'Cette prestation a déjà des créneaux : elle a été désactivée.');
        }

        return $this->redirectToRoute('pro_services');
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/services/reorder', name: 'pro_service_reorder', methods: ['POST'])]
    public function reorder(#[MapRequestPayload(validate: false)] OrderInput $input): RedirectResponse
    {
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::SERVICES_MANAGE, $establishment);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->catalog->reorder($establishment, array_map('intval', $input->ids)), 'Ordre des prestations enregistré.');
        }

        return $this->redirectToRoute('pro_services');
    }

    private function input(Request $request): ServiceInput
    {
        $price = str_replace([' ', ','], ['', '.'], $request->request->getString('price'));
        $discount = $request->request->getString('defaultDiscount');
        $input = new ServiceInput();
        $input->name = trim($request->request->getString('name')) ?: null;
        $input->description = trim($request->request->getString('description')) ?: null;
        $input->price = is_numeric($price) ? (int) round((float) $price * 100) : null;
        $input->duration = $request->request->getInt('duration') ?: null;
        $input->defaultDiscount = $discount !== '' ? (int) $discount : null;

        return $input;
    }

    private function findService(int $id): EstablishmentService
    {
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::SERVICES_MANAGE, $establishment);

        return $this->services->findOneInEstablishment($establishment, $id) ?? throw $this->createNotFoundException('Prestation introuvable.');
    }
}