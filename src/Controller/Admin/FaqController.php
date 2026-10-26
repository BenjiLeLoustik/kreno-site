<?php

namespace App\Controller\Admin;

use App\Catalog\Dto\PositionsInput;
use App\Faq\Dto\FaqCategoryInput;
use App\Faq\Dto\FaqOrderInput;
use App\Faq\Dto\FaqQuestionInput;
use App\Faq\Enum\FaqFlash;
use App\Faq\Provider\AdminFaqPageProvider;
use App\Faq\Service\FaqCategoryService;
use App\Faq\Service\FaqQuestionService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class FaqController extends AbstractAdminController
{
    public function __construct(
        private FaqCategoryService $categoryService,
        private FaqQuestionService $questionService,
        private AdminFaqPageProvider $pages,
    ) {
    }

    #[Route(
        path: '/admin/faq',
        name: 'admin_faq_index',
        methods: ['GET']
    )]
    public function index(): Response
    {
        $this->currentAdmin();

        return $this->render('admin/faq/index.html.twig', $this->pages->index());
    }

    #[Route(
        path: '/admin/faq/categories/{slug}',
        name: 'admin_faq_category_show',
        methods: ['GET']
    )]
    public function showCategory(Request $request, string $slug): Response
    {
        $this->currentAdmin();
        $locale = $this->pages->locale($request->query->getString('locale') ?: null);

        return $this->render('admin/faq/category_show.html.twig', $this->pages->categoryShow($slug, $locale));
    }

    #[Route(
        path: '/admin/faq/questions/{id}',
        name: 'admin_faq_question_show',
        methods: ['GET'],
        requirements: ['id' => '\d+'],
    )]
    public function showQuestion(Request $request, int $id): Response
    {
        $this->currentAdmin();
        $locale = $this->pages->locale($request->query->getString('locale') ?: null);

        return $this->render('admin/faq/question_show.html.twig', $this->pages->questionShow($id, $locale));
    }

    #[Csrf(id: 'admin_faq')]
    #[Route(
        path: '/admin/faq/categories',
        name: 'admin_faq_category_create',
        methods: ['POST']
    )]
    public function createCategory(
        #[MapRequestPayload(validate: false)]
        FaqCategoryInput $input
    ): RedirectResponse {
        $admin = $this->currentAdmin();

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->categoryService->create($input, $admin),
                FaqFlash::CATEGORY_CREATED->text($input->name),
            );
        }

        return $this->redirectToRoute('admin_faq_index');
    }

    #[Csrf(id: 'admin_faq')]
    #[Route(
        path: '/admin/faq/categories/order',
        name: 'admin_faq_category_order',
        methods: ['POST']
    )]
    public function reorderCategories(
        #[MapRequestPayload(validate: false)]
        PositionsInput $input
    ): RedirectResponse {
        $admin = $this->currentAdmin();

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->categoryService->reorder($input->slugs, $admin),
                FaqFlash::CATEGORIES_REORDERED->text(),
            );
        }

        return $this->redirectToRoute('admin_faq_index');
    }

    #[Csrf(id: 'admin_faq')]
    #[Route(
        path: '/admin/faq/categories/{slug}',
        name: 'admin_faq_category_update',
        methods: ['POST']
    )]
    public function updateCategory(
        Request $request,
        string $slug,
        #[MapRequestPayload(validate: false)]
        FaqCategoryInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin();
        $locale = $this->pages->locale($request->query->getString('locale') ?: null);
        $category = $this->pages->category($slug);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->categoryService->update($category, $input, $admin, $locale),
                FaqFlash::CATEGORY_UPDATED->text($input->name),
            );
        }

        return $this->redirectToRoute('admin_faq_category_show', [
            'slug' => $this->pages->slug($category) ?? $slug,
            'locale' => $locale,
        ]);
    }

    #[Csrf(id: 'admin_faq')]
    #[Route(
        path: '/admin/faq/categories/{slug}/visibility',
        name: 'admin_faq_category_visibility',
        methods: ['POST']
    )]
    public function toggleCategory(string $slug): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $category = $this->pages->category($slug);
        $visible = !$category->isVisible();
        $this->attempt(
            fn () => $this->categoryService->setVisible($category, $visible, $admin),
            FaqFlash::categoryVisibility($visible)->text($this->pages->name($category)),
        );

        return $this->redirectToRoute('admin_faq_index');
    }

    #[Csrf(id: 'admin_faq')]
    #[Route(
        path: '/admin/faq/categories/{slug}/delete',
        name: 'admin_faq_category_delete',
        methods: ['POST']
    )]
    public function deleteCategory(string $slug): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $category = $this->pages->category($slug);
        $this->attempt(
            fn () => $this->categoryService->delete($category, $admin),
            FaqFlash::CATEGORY_DELETED->text($this->pages->name($category)),
        );

        return $this->redirectToRoute('admin_faq_index');
    }

    #[Csrf(id: 'admin_faq')]
    #[Route(
        path: '/admin/faq/categories/{slug}/questions',
        name: 'admin_faq_question_create',
        methods: ['POST']
    )]
    public function createQuestion(
        Request $request,
        string $slug,
        #[MapRequestPayload(validate: false)] FaqQuestionInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin();
        $locale = $this->pages->locale($request->query->getString('locale') ?: null);
        $category = $this->pages->category($slug);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->questionService->add($category, $input, $admin, $locale),
                FaqFlash::QUESTION_CREATED->text($this->pages->name($category)),
            );
        }

        return $this->redirectToRoute('admin_faq_category_show', [
            'slug' => $slug,
            'locale' => $locale,
        ]);
    }

    #[Csrf(id: 'admin_faq')]
    #[Route(
        path: '/admin/faq/categories/{slug}/questions/order',
        name: 'admin_faq_question_order',
        methods: ['POST']
    )]
    public function reorderQuestions(
        string $slug,
        #[MapRequestPayload(validate: false)]
        FaqOrderInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin();
        $category = $this->pages->category($slug);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->questionService->reorder($category, $input->ids, $admin),
                FaqFlash::QUESTIONS_REORDERED->text(),
            );
        }

        return $this->redirectToRoute('admin_faq_category_show', [
            'slug' => $slug
        ]);
    }

    #[Csrf(id: 'admin_faq')]
    #[Route(
        path: '/admin/faq/questions/{id}',
        name: 'admin_faq_question_update',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function updateQuestion(
        Request $request,
        int $id,
        #[MapRequestPayload(validate: false)]
        FaqQuestionInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin();
        $locale = $this->pages->locale($request->query->getString('locale') ?: null);
        $question = $this->pages->question($id);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->questionService->update($question, $input, $admin, $locale),
                FaqFlash::QUESTION_UPDATED->text(),
            );
        }

        return $this->redirectToRoute('admin_faq_question_show', [
            'id' => $id,
            'locale' => $locale,
        ]);
    }

    #[Csrf(id: 'admin_faq')]
    #[Route(
        path: '/admin/faq/questions/{id}/visibility',
        name: 'admin_faq_question_visibility',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function toggleQuestion(int $id): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $question = $this->pages->question($id);
        $visible = !$question->isVisible();
        $this->attempt(
            fn () => $this->questionService->setVisible($question, $visible, $admin),
            FaqFlash::questionVisibility($visible)->text(),
        );

        return $this->redirectToRoute('admin_faq_category_show', [
            'slug' => $this->pages->slug($question->getCategory())
        ]);
    }

    #[Csrf(id: 'admin_faq')]
    #[Route(
        path: '/admin/faq/questions/{id}/delete',
        name: 'admin_faq_question_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function deleteQuestion(int $id): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $question = $this->pages->question($id);
        $category = $question->getCategory();
        $this->attempt(fn () => $this->questionService->delete($question, $admin), FaqFlash::QUESTION_DELETED->text());

        return $this->redirectToRoute('admin_faq_category_show', [
            'slug' => $this->pages->slug($category)
        ]);
    }
}