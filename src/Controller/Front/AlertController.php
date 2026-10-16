<?php

namespace App\Controller\Front;

use App\Entity\Category;
use App\Entity\City;
use App\Entity\User\User;
use App\Entity\User\UserAlert;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\User\UserAlertRepository;
use App\User\Dto\AlertInput;
use App\User\Presenter\AlertPresenter;
use App\User\Service\AlertService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class AlertController extends AbstractFrontController
{
    public const MAX_ALERTS = 5;

    public function __construct(
        private UserAlertRepository $alerts,
        private AlertService $alertService,
        private AlertPresenter $presenter,
        private CategoryRepository $categories,
        private CityRepository $cities,
    ) {
    }

    #[Route('/account/alerts', name: 'account_alerts', methods: ['GET'])]
    public function index(): Response
    {
        $alerts = $this->presenter->presentAll($this->alerts->findByUser($this->currentUser()));

        return $this->render('front/account/alert/index.html.twig', [
            'alerts' => $alerts,
            'canCreate' => count($alerts) < self::MAX_ALERTS,
            'maxAlerts' => self::MAX_ALERTS,
            'categories' => array_map(static fn (Category $category): array => ['name' => $category->getName(), 'slug' => $category->getSlug()], $this->categories->findVisible()),
            'cities' => array_map(static fn (City $city): array => ['name' => $city->getName(), 'slug' => $city->getSlug()], $this->cities->findOpen()),
        ]);
    }

    #[Csrf('account')]
    #[Route('/account/alerts', name: 'account_alert_create', methods: ['POST'])]
    public function create(Request $request): RedirectResponse
    {
        $input = $this->input($request);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->alertService->create($this->currentUser(), $input), 'Alerte créée : nous vous prévenons dès qu\'un créneau correspond.');
        }

        return $this->redirectToRoute('account_alerts');
    }

    #[Csrf('account')]
    #[Route('/account/alerts/{id}', name: 'account_alert_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(Request $request, int $id): RedirectResponse
    {
        $alert = $this->findOwnAlert($id, $this->currentUser());
        $input = $this->input($request);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->alertService->update($alert, $input), 'Alerte enregistrée.');
        }

        return $this->redirectToRoute('account_alerts');
    }

    #[Csrf('account')]
    #[Route('/account/alerts/{id}/delete', name: 'account_alert_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id): RedirectResponse
    {
        $alert = $this->findOwnAlert($id, $this->currentUser());
        $this->attempt(fn () => $this->alertService->delete($alert), 'Alerte supprimée.');

        return $this->redirectToRoute('account_alerts');
    }

    private function input(Request $request): AlertInput
    {
        $slugs = $request->request->get('categorySlugs');
        $maxPrice = trim($request->request->getString('maxPrice'));

        $input = new AlertInput();
        $input->label = trim($request->request->getString('label')) ?: null;
        $input->citySlug = $request->request->getString('citySlug');
        $input->radiusKm = $request->request->getInt('radiusKm', 3);
        $input->startTime = $request->request->getString('startTime');
        $input->endTime = $request->request->getString('endTime');
        $input->maxPrice = $maxPrice !== '' && is_numeric($maxPrice) ? (int) round((float) $maxPrice * 100) : 0;
        $input->categorySlugs = is_array($slugs) ? array_values(array_filter($slugs, 'is_string')) : [];
        $input->enabled = $request->request->getBoolean('enabled');

        return $input;
    }

    private function findOwnAlert(int $id, User $user): UserAlert
    {
        $alert = $this->alerts->find($id);

        if (!$alert instanceof UserAlert || $alert->getUser() !== $user) {
            throw $this->createNotFoundException('Alerte introuvable.');
        }

        return $alert;
    }
}