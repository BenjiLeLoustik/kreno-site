<?php

namespace App\Controller\Admin;

use App\Catalog\Dto\CategoryInput;
use App\Catalog\Dto\CityInput;
use App\Catalog\Dto\PositionsInput;
use App\Catalog\Dto\ServiceSuggestionInput;
use App\Catalog\Enum\CityStatus;
use App\Catalog\Presenter\CategoryPresenter;
use App\Catalog\Presenter\CityPresenter;
use App\Catalog\Service\CategoryService;
use App\Catalog\Service\CityService;
use App\Catalog\Service\ServiceSuggestionService;
use App\Entity\Category;
use App\Entity\CategoryServiceSuggestion;
use App\Entity\City;
use App\Establishment\Helper\ServiceSuggestionHelper;
use App\Repository\CategoryRepository;
use App\Repository\CategoryServiceSuggestionRepository;
use App\Repository\CityRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class CatalogController extends AbstractAdminController
{
    public function __construct(
        private CityRepository $cities,
        private CategoryRepository $categories,
        private CategoryServiceSuggestionRepository $suggestions,
        private CompanyEstablishmentRepository $establishments,
        private CityService $cityService,
        private CategoryService $categoryService,
        private ServiceSuggestionService $suggestionService,
        private CityPresenter $cityPresenter,
        private CategoryPresenter $categoryPresenter,
    ) {
    }

    #[Route('/admin/catalog', name: 'admin_catalog_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->currentAdmin();

        return $this->render('admin/catalog/index.html.twig', [
            'categories' => array_map(fn (Category $category): array => $this->categoryPresenter->detail($category) + [
                    'establishmentsCount' => $this->establishments->countByCategory($category),
                ], $this->categories->findAllForAdmin()),
            'cities' => array_map(fn (City $city): array => $this->cityPresenter->detail($city) + [
                    'establishmentsCount' => $this->establishments->countByCity($city),
                ], $this->cities->findAllForAdmin()),
            'cityStatuses' => array_map(static fn (CityStatus $status): array => ['value' => $status->value, 'label' => $status->label()], CityStatus::cases()),
            'durations' => ServiceSuggestionHelper::DURATIONS,
        ]);
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/categories', name: 'admin_catalog_category_create', methods: ['POST'])]
    public function createCategory(#[MapRequestPayload(validate: false)] CategoryInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->categoryService->create($input, $admin), sprintf('Catégorie « %s » ajoutée.', $input->name));
        }

        return $this->back();
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/categories/order', name: 'admin_catalog_category_order', methods: ['POST'])]
    public function reorderCategories(#[MapRequestPayload(validate: false)] PositionsInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->categoryService->reorder($input->slugs, $admin), 'Ordre des catégories enregistré.');
        }

        return $this->back();
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/categories/{slug}', name: 'admin_catalog_category_update', methods: ['POST'])]
    public function updateCategory(string $slug, #[MapRequestPayload(validate: false)] CategoryInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $category = $this->findCategory($slug);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->categoryService->update($category, $input, $admin), sprintf('Catégorie « %s » modifiée.', $category->getName()));
        }

        return $this->back();
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/categories/{slug}/visibility', name: 'admin_catalog_category_visibility', methods: ['POST'])]
    public function toggleCategory(string $slug): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $category = $this->findCategory($slug);
        $visible = !$category->isVisible();
        $this->attempt(fn () => $this->categoryService->setVisible($category, $visible, $admin), sprintf('Catégorie « %s » %s.', $category->getName(), $visible ? 'visible' : 'masquée'));

        return $this->back();
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/categories/{slug}/delete', name: 'admin_catalog_category_delete', methods: ['POST'])]
    public function deleteCategory(string $slug): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $category = $this->findCategory($slug);
        $name = $category->getName();
        $this->attempt(fn () => $this->categoryService->delete($category, $admin), sprintf('Catégorie « %s » supprimée.', $name));

        return $this->back();
    }

    #[Csrf('admin_catalog')]
    #[Route(
        '/admin/catalog/categories/{slug}/suggestions',
        name: 'admin_catalog_suggestion_create',
        methods: ['POST'],
    )]
    public function createSuggestion(
        string $slug,
        #[MapRequestPayload(validate: false)] ServiceSuggestionInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin();
        $category = $this->findCategory($slug);

        if (!$this->invalid($input)) {
            $message = sprintf('Suggestion « %s » ajoutée.', $input->name);
            $this->attempt(fn () => $this->suggestionService->add($category, $input, $admin), $message);
        }

        return $this->back();
    }

    #[Csrf('admin_catalog')]
    #[Route(
        '/admin/catalog/suggestions/{id}',
        name: 'admin_catalog_suggestion_update',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function updateSuggestion(
        int $id,
        #[MapRequestPayload(validate: false)] ServiceSuggestionInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin();
        $suggestion = $this->findSuggestion($id);

        if (!$this->invalid($input)) {
            $message = sprintf('Suggestion « %s » modifiée.', $input->name);
            $this->attempt(fn () => $this->suggestionService->update($suggestion, $input, $admin), $message);
        }

        return $this->back();
    }

    #[Csrf('admin_catalog')]
    #[Route(
        '/admin/catalog/suggestions/{id}/delete',
        name: 'admin_catalog_suggestion_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function deleteSuggestion(int $id): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $suggestion = $this->findSuggestion($id);
        $message = sprintf('Suggestion « %s » supprimée.', $suggestion->getName());
        $this->attempt(fn () => $this->suggestionService->delete($suggestion, $admin), $message);

        return $this->back();
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/cities', name: 'admin_catalog_city_create', methods: ['POST'])]
    public function createCity(#[MapRequestPayload(validate: false)] CityInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->cityService->create($input, $admin), sprintf('Ville « %s » ajoutée.', $input->name));
        }

        return $this->back();
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/cities/order', name: 'admin_catalog_city_order', methods: ['POST'])]
    public function reorderCities(#[MapRequestPayload(validate: false)] PositionsInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->cityService->reorder($input->slugs, $admin), 'Ordre des villes enregistré.');
        }

        return $this->back();
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/cities/{slug}', name: 'admin_catalog_city_update', methods: ['POST'])]
    public function updateCity(string $slug, #[MapRequestPayload(validate: false)] CityInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $city = $this->findCity($slug);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->cityService->update($city, $input, $admin), sprintf('Ville « %s » modifiée.', $city->getName()));
        }

        return $this->back();
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/cities/{slug}/status/{status}', name: 'admin_catalog_city_status', methods: ['POST'])]
    public function updateCityStatus(string $slug, string $status): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $city = $this->findCity($slug);
        $newStatus = CityStatus::tryFrom($status) ?? throw $this->createNotFoundException('Statut {status} inconnu.', ['status' => $status]);
        $this->attempt(fn () => $this->cityService->updateStatus($city, $newStatus, $admin), sprintf('%s : %s.', $city->getName(), mb_strtolower($newStatus->label())));

        return $this->back();
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/cities/{slug}/delete', name: 'admin_catalog_city_delete', methods: ['POST'])]
    public function deleteCity(string $slug): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $city = $this->findCity($slug);
        $name = $city->getName();
        $this->attempt(fn () => $this->cityService->delete($city, $admin), sprintf('Ville « %s » supprimée.', $name));

        return $this->back();
    }

    private function back(): RedirectResponse
    {
        return $this->redirectToRoute('admin_catalog_index');
    }

    private function findCategory(string $slug): Category
    {
        return $this->categories->findOneBySlug($slug) ?? throw $this->createNotFoundException('La catégorie {slug} n\'existe pas.', ['slug' => $slug]);
    }

    private function findSuggestion(int $id): CategoryServiceSuggestion
    {
        $suggestion = $this->suggestions->findOneById($id);

        return $suggestion ?? throw $this->createNotFoundException('La suggestion {id} n\'existe pas.', ['id' => $id]);
    }

    private function findCity(string $slug): City
    {
        return $this->cities->findOneBySlug($slug) ?? throw $this->createNotFoundException('La ville {slug} n\'existe pas.', ['slug' => $slug]);
    }
}