<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Establishment\Dto\OrderInput;
use App\Establishment\Enum\ServiceFlash;
use App\Establishment\Mapper\OrderRequestMapper;
use App\Establishment\Mapper\ServiceRequestMapper;
use App\Establishment\Provider\ServicePageProvider;
use App\Establishment\Service\ServiceCatalogService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class ServiceController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private ServiceCatalogService $catalog,
        private ServicePageProvider $pages,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route('/pro/establishment/services', name: 'pro_services', methods: ['GET'])]
    public function index(): Response
    {
        $member = $this->currentMember();
        $establishment = $this->permittedEstablishment(Permission::PLANNING_VIEW);

        return $this->renderPro('pro/service/index.html.twig', $this->pages->index($member, $establishment));
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/services', name: 'pro_service_create', methods: ['POST'])]
    public function create(Request $request): RedirectResponse
    {
        $establishment = $this->permittedEstablishment(Permission::SERVICES_MANAGE);
        $input = ServiceRequestMapper::service($request);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->catalog->create($establishment, $input),
                ServiceFlash::CREATED->text($input->name),
            );
        }

        return $this->redirectToRoute('pro_services');
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/establishment/services/{id}',
        name: 'pro_service_update',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function update(Request $request, int $id): RedirectResponse
    {
        $service = $this->pages->owned($this->permittedEstablishment(Permission::SERVICES_MANAGE), $id);
        $input = ServiceRequestMapper::service($request);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->catalog->update($service, $input),
                ServiceFlash::UPDATED->text($input->name),
            );
        }

        return $this->redirectToRoute('pro_services');
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/establishment/services/{id}/toggle',
        name: 'pro_service_toggle',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function toggle(int $id): RedirectResponse
    {
        $service = $this->pages->owned($this->permittedEstablishment(Permission::SERVICES_MANAGE), $id);
        $active = !$service->isActive();
        $this->attempt(
            fn () => $this->catalog->setActive($service, $active),
            ServiceFlash::toggled($active)->text($service->getName()),
        );

        return $this->redirectToRoute('pro_services');
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/establishment/services/{id}/delete',
        name: 'pro_service_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function delete(int $id): RedirectResponse
    {
        $service = $this->pages->owned($this->permittedEstablishment(Permission::SERVICES_MANAGE), $id);
        $deleted = null;
        $this->attempt(function () use ($service, &$deleted): void {
            $deleted = $this->catalog->delete($service);
        });

        if ($deleted !== null) {
            $this->addFlash('success', ServiceFlash::afterDelete($deleted)->text());
        }

        return $this->redirectToRoute('pro_services');
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/services/reorder', name: 'pro_service_reorder', methods: ['POST'])]
    public function reorder(#[MapRequestPayload(validate: false)] OrderInput $input): RedirectResponse
    {
        $establishment = $this->permittedEstablishment(Permission::SERVICES_MANAGE);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->catalog->reorder($establishment, OrderRequestMapper::ids($input)),
                ServiceFlash::REORDERED->text(),
            );
        }

        return $this->redirectToRoute('pro_services');
    }
}