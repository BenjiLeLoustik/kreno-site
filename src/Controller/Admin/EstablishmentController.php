<?php

namespace App\Controller\Admin;

use App\Catalog\Presenter\CategoryPresenter;
use App\Catalog\Presenter\CityPresenter;
use App\Entity\Company\CompanyEstablishment;
use App\Establishment\Dto\SuspensionInput;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Presenter\AdminEstablishmentPresenter;
use App\Establishment\Service\EstablishmentAdminService;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\User\Enum\UserRole;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class EstablishmentController extends AbstractAdminController
{
    public const STATUS_FILTERS = [
        'all' => ['label' => 'Tous', 'statuses' => [EstablishmentStatus::ONLINE, EstablishmentStatus::PAUSED]],
        'online' => ['label' => 'En ligne', 'statuses' => [EstablishmentStatus::ONLINE]],
        'suspended' => ['label' => 'Suspendus', 'statuses' => [EstablishmentStatus::PAUSED]],
    ];

    public function __construct(
        private CompanyEstablishmentRepository $establishments,
        private CategoryRepository $categories,
        private CityRepository $cities,
        private EstablishmentAdminService $admin,
        private AdminEstablishmentPresenter $presenter,
        private CategoryPresenter $categoryPresenter,
        private CityPresenter $cityPresenter,
    ) {
    }

    #[Route('/admin/establishments', name: 'admin_establishments', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);

        return $this->page($request, null);
    }

    #[Route('/admin/establishments/{id}', name: 'admin_establishment_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Request $request, int $id): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);

        return $this->page($request, $this->findEstablishment($id));
    }

    #[Csrf('admin_establishment')]
    #[Route('/admin/establishments/{id}/suspend', name: 'admin_establishment_suspend', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function suspend(int $id, #[MapRequestPayload(validate: false)] SuspensionInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $establishment = $this->findEstablishment($id);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->admin->suspend($establishment, $admin, (string) $input->reason), sprintf('%s est suspendu. Le pro a été prévenu par e-mail.', $establishment->getName()));
        }

        return $this->redirectToRoute('admin_establishment_show', ['id' => $id]);
    }

    #[Csrf('admin_establishment')]
    #[Route('/admin/establishments/{id}/resume', name: 'admin_establishment_resume', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function resume(int $id): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $establishment = $this->findEstablishment($id);
        $this->attempt(fn () => $this->admin->resume($establishment, $admin), sprintf('%s est de nouveau en ligne.', $establishment->getName()));

        return $this->redirectToRoute('admin_establishment_show', ['id' => $id]);
    }

    private function page(Request $request, ?CompanyEstablishment $selected): Response
    {
        $category = $request->query->getString('category') !== '' ? $this->categories->findOneBySlug($request->query->getString('category')) : null;
        $city = $request->query->getString('city') !== '' ? $this->cities->findOneBySlug($request->query->getString('city')) : null;
        $status = $request->query->getString('status', 'all');
        $status = isset(self::STATUS_FILTERS[$status]) ? $status : 'all';
        $search = trim($request->query->getString('q'));
        $items = $this->establishments->findForAdmin($category, $city, self::STATUS_FILTERS[$status]['statuses'], $search !== '' ? $search : null);
        $selected ??= $items[0] ?? null;

        return $this->render('admin/establishment/index.html.twig', [
            'filters' => [
                'category' => $category?->getSlug(),
                'city' => $city?->getSlug(),
                'status' => $status,
                'q' => $search,
            ],
            'categories' => $this->categoryPresenter->summaries($this->categories->findAllForAdmin()),
            'cities' => $this->cityPresenter->summaries($this->cities->findAllForAdmin()),
            'statuses' => array_map(static fn (string $key, array $filter): array => ['key' => $key, 'label' => $filter['label']], array_keys(self::STATUS_FILTERS), self::STATUS_FILTERS),
            'establishments' => $this->presenter->items($items),
            'selected' => $selected !== null ? $this->presenter->detail($selected) : null,
        ]);
    }

    private function findEstablishment(int $id): CompanyEstablishment
    {
        $establishment = $this->establishments->find($id);

        return $establishment instanceof CompanyEstablishment ? $establishment : throw $this->createNotFoundException('Établissement introuvable.');
    }
}