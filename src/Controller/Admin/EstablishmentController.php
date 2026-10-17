<?php

namespace App\Controller\Admin;

use App\Establishment\Dto\SuspensionInput;
use App\Establishment\Enum\AdminEstablishmentFlash;
use App\Establishment\Mapper\AdminEstablishmentRequestMapper;
use App\Establishment\Provider\AdminEstablishmentPageProvider;
use App\Establishment\Service\EstablishmentAdminService;
use App\Shared\Mapper\AdminQueryMapper;
use App\User\Enum\UserRole;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class EstablishmentController extends AbstractAdminController
{
    public function __construct(
        private EstablishmentAdminService $admin,
        private AdminEstablishmentPageProvider $pages,
    ) {
    }

    #[Route('/admin/establishments', name: 'admin_establishments', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);
        $page = $this->pages->index(
            AdminQueryMapper::optional($request, 'category'),
            AdminQueryMapper::optional($request, 'city'),
            AdminEstablishmentRequestMapper::filter($request),
            AdminQueryMapper::search($request),
        );

        return $this->render('admin/establishment/index.html.twig', $page);
    }

    #[Route(
        '/admin/establishments/{id}',
        name: 'admin_establishment_show',
        methods: ['GET'],
        requirements: ['id' => '\d+'],
    )]
    public function show(Request $request, int $id): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);
        $page = $this->pages->index(
            AdminQueryMapper::optional($request, 'category'),
            AdminQueryMapper::optional($request, 'city'),
            AdminEstablishmentRequestMapper::filter($request),
            AdminQueryMapper::search($request),
            $this->pages->establishment($id),
        );

        return $this->render('admin/establishment/index.html.twig', $page);
    }

    #[Csrf('admin_establishment')]
    #[Route(
        '/admin/establishments/{id}/suspend',
        name: 'admin_establishment_suspend',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function suspend(int $id, #[MapRequestPayload(validate: false)] SuspensionInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $establishment = $this->pages->establishment($id);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->admin->suspend($establishment, $admin, (string) $input->reason),
                AdminEstablishmentFlash::SUSPENDED->text($establishment->getName()),
            );
        }

        return $this->redirectToRoute('admin_establishment_show', ['id' => $id]);
    }

    #[Csrf('admin_establishment')]
    #[Route(
        '/admin/establishments/{id}/resume',
        name: 'admin_establishment_resume',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function resume(int $id): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $establishment = $this->pages->establishment($id);
        $this->attempt(
            fn () => $this->admin->resume($establishment, $admin),
            AdminEstablishmentFlash::RESUMED->text($establishment->getName()),
        );

        return $this->redirectToRoute('admin_establishment_show', ['id' => $id]);
    }
}