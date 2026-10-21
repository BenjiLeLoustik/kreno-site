<?php

namespace App\Controller\Admin;

use App\Catalog\Dto\CategoryInput;
use App\Catalog\Dto\CityInput;
use App\Catalog\Dto\PositionsInput;
use App\Catalog\Dto\ServiceSuggestionInput;
use App\Catalog\Enum\CatalogFlash;
use App\Catalog\Provider\AdminCatalogPageProvider;
use App\Catalog\Service\CategoryService;
use App\Catalog\Service\CityService;
use App\Catalog\Service\ServiceSuggestionService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class CatalogController extends AbstractAdminController
{
    public function __construct(
        private CityService $cityService,
        private CategoryService $categoryService,
        private ServiceSuggestionService $suggestionService,
        private AdminCatalogPageProvider $pages,
        private TranslationManagerInterface $translation,
    ) {
    }

    #[Route('/admin/catalog', name: 'admin_catalog_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->currentAdmin();

        return $this->render('admin/catalog/index.html.twig', $this->pages->index());
    }

    #[Route('/admin/catalog/categories/{slug}', name: 'admin_catalog_category_show', methods: ['GET'])]
    public function showCategory(Request $request, string $slug): Response
    {
        $this->currentAdmin();
        $locale = $request->query->getString('locale', $this->translation->getLocale());

        return $this->render('admin/catalog/category_show.html.twig', $this->pages->categoryShow($slug, $locale));
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/categories', name: 'admin_catalog_category_create', methods: ['POST'])]
    public function createCategory(#[MapRequestPayload(validate: false)] CategoryInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->categoryService->create($input, $admin),
                CatalogFlash::CATEGORY_CREATED->text($input->name),
            );
        }

        return $this->redirectToRoute('admin_catalog_index');
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/categories/order', name: 'admin_catalog_category_order', methods: ['POST'])]
    public function reorderCategories(#[MapRequestPayload(validate: false)] PositionsInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->categoryService->reorder($input->slugs, $admin),
                CatalogFlash::CATEGORIES_REORDERED->text(),
            );
        }

        return $this->redirectToRoute('admin_catalog_index');
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/categories/{slug}', name: 'admin_catalog_category_update', methods: ['POST'])]
    public function updateCategory(
        Request $request,
        string $slug,
        #[MapRequestPayload(validate: false)] CategoryInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin();
        $locale = $request->query->getString('locale', $this->translation->getLocale());
        $category = $this->pages->category($slug);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->categoryService->update($category, $input, $admin, $locale),
                CatalogFlash::CATEGORY_UPDATED->text($input->name),
            );
        }

        return $this->redirectToRoute('admin_catalog_category_show', ['slug' => $slug, 'locale' => $locale]);
    }

    #[Csrf('admin_catalog')]
    #[Route(
        '/admin/catalog/categories/{slug}/visibility',
        name: 'admin_catalog_category_visibility',
        methods: ['POST'],
    )]
    public function toggleCategory(string $slug): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $category = $this->pages->category($slug);
        $visible = !$category->isVisible();
        $this->attempt(
            fn () => $this->categoryService->setVisible($category, $visible, $admin),
            CatalogFlash::categoryVisibility($visible)->text($category->getName()),
        );

        return $this->redirectToRoute('admin_catalog_index');
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/categories/{slug}/delete', name: 'admin_catalog_category_delete', methods: ['POST'])]
    public function deleteCategory(string $slug): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $category = $this->pages->category($slug);
        $this->attempt(
            fn () => $this->categoryService->delete($category, $admin),
            CatalogFlash::CATEGORY_DELETED->text($category->getName()),
        );

        return $this->redirectToRoute('admin_catalog_index');
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
        $category = $this->pages->category($slug);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->suggestionService->add($category, $input, $admin),
                CatalogFlash::SUGGESTION_CREATED->text($input->name),
            );
        }

        return $this->redirectToRoute('admin_catalog_category_show', ['slug' => $slug]);
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
        $suggestion = $this->pages->suggestion($id);
        $category = $suggestion->getCategory();

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->suggestionService->update($suggestion, $input, $admin),
                CatalogFlash::SUGGESTION_UPDATED->text($input->name),
            );
        }

        return $this->redirectToRoute('admin_catalog_category_show', ['slug' => $category?->getSlug()]);
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
        $suggestion = $this->pages->suggestion($id);
        $category = $suggestion->getCategory();
        $this->attempt(
            fn () => $this->suggestionService->delete($suggestion, $admin),
            CatalogFlash::SUGGESTION_DELETED->text($suggestion->getName()),
        );

        return $this->redirectToRoute('admin_catalog_category_show', ['slug' => $category?->getSlug()]);
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/cities', name: 'admin_catalog_city_create', methods: ['POST'])]
    public function createCity(#[MapRequestPayload(validate: false)] CityInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->cityService->create($input, $admin),
                CatalogFlash::CITY_CREATED->text($input->name),
            );
        }

        return $this->redirectToRoute('admin_catalog_index');
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/cities/order', name: 'admin_catalog_city_order', methods: ['POST'])]
    public function reorderCities(#[MapRequestPayload(validate: false)] PositionsInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->cityService->reorder($input->slugs, $admin),
                CatalogFlash::CITIES_REORDERED->text(),
            );
        }

        return $this->redirectToRoute('admin_catalog_index');
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/cities/{slug}', name: 'admin_catalog_city_update', methods: ['POST'])]
    public function updateCity(string $slug, #[MapRequestPayload(validate: false)] CityInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $city = $this->pages->city($slug);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->cityService->update($city, $input, $admin),
                CatalogFlash::CITY_UPDATED->text($city->getName()),
            );
        }

        return $this->redirectToRoute('admin_catalog_index');
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/cities/{slug}/status/{status}', name: 'admin_catalog_city_status', methods: ['POST'])]
    public function updateCityStatus(string $slug, string $status): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $city = $this->pages->city($slug);
        $newStatus = $this->pages->cityStatus($status);
        $this->attempt(
            fn () => $this->cityService->updateStatus($city, $newStatus, $admin),
            CatalogFlash::cityStatus($city->getName(), $newStatus),
        );

        return $this->redirectToRoute('admin_catalog_index');
    }

    #[Csrf('admin_catalog')]
    #[Route('/admin/catalog/cities/{slug}/delete', name: 'admin_catalog_city_delete', methods: ['POST'])]
    public function deleteCity(string $slug): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $city = $this->pages->city($slug);
        $this->attempt(
            fn () => $this->cityService->delete($city, $admin),
            CatalogFlash::CITY_DELETED->text($city->getName()),
        );

        return $this->redirectToRoute('admin_catalog_index');
    }
}