<?php

namespace App\Controller\Front;

use App\User\Enum\AccountFlash;
use App\User\Mapper\AlertRequestMapper;
use App\User\Provider\AlertPageProvider;
use App\User\Service\AlertService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class AlertController extends AbstractFrontController
{
    public function __construct(
        private AlertService $alerts,
        private AlertPageProvider $pages,
    ) {
    }

    #[Route('/account/alerts', name: 'account_alerts', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('front/account/alert/index.html.twig', $this->pages->index($this->currentUser()));
    }

    #[Csrf('account')]
    #[Route('/account/alerts', name: 'account_alert_create', methods: ['POST'])]
    public function create(Request $request): RedirectResponse
    {
        $input = AlertRequestMapper::alert($request);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->alerts->create($this->currentUser(), $input),
                AccountFlash::ALERT_CREATED->text(),
            );
        }

        return $this->redirectToRoute('account_alerts');
    }

    #[Csrf('account')]
    #[Route('/account/alerts/{id}', name: 'account_alert_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(Request $request, int $id): RedirectResponse
    {
        $alert = $this->pages->owned($id, $this->currentUser());
        $input = AlertRequestMapper::alert($request);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->alerts->update($alert, $input), AccountFlash::ALERT_SAVED->text());
        }

        return $this->redirectToRoute('account_alerts');
    }

    #[Csrf('account')]
    #[Route(
        '/account/alerts/{id}/delete',
        name: 'account_alert_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function delete(int $id): RedirectResponse
    {
        $alert = $this->pages->owned($id, $this->currentUser());
        $this->attempt(fn () => $this->alerts->delete($alert), AccountFlash::ALERT_DELETED->text());

        return $this->redirectToRoute('account_alerts');
    }
}