<?php

namespace App\Controller\Front;

use App\Shared\Helper\DownloadHelper;
use App\User\Enum\AccountFlash;
use App\User\Mapper\AccountRequestMapper;
use App\User\Provider\AccountPageProvider;
use App\User\Service\AccountService;
use App\User\Service\DataExportService;
use App\User\Service\PasswordService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class AccountController extends AbstractFrontController
{
    public function __construct(
        private AccountService $accounts,
        private PasswordService $passwords,
        private DataExportService $exports,
        private AccountPageProvider $pages,
    ) {
    }

    #[Route('/account', name: 'account_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('front/account/index.html.twig', $this->pages->index($this->currentUser()));
    }

    #[Csrf('account')]
    #[Route('/account/profile', name: 'account_profile', methods: ['POST'])]
    public function profile(Request $request): RedirectResponse
    {
        $input = AccountRequestMapper::profile($request);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->accounts->updateProfile($this->currentUser(), $input),
                AccountFlash::PROFILE_SAVED->text(),
            );
        }

        return $this->redirect($this->generateUrl('account_settings') . '#informations');
    }

    #[Csrf('account')]
    #[Route('/account/password', name: 'account_password', methods: ['POST'])]
    public function password(Request $request): RedirectResponse
    {
        $input = AccountRequestMapper::password($request);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->passwords->change($this->currentUser(), $input),
                AccountFlash::PASSWORD_CHANGED->text(),
            );
        }

        return $this->redirect($this->generateUrl('account_settings') . '#mot-de-passe');
    }

    #[Route('/account/settings', name: 'account_settings', methods: ['GET'])]
    public function settings(): Response
    {
        return $this->render('front/account/settings.html.twig', $this->pages->settings($this->currentUser()));
    }

    #[Csrf('account')]
    #[Route('/account/settings', name: 'account_settings_update', methods: ['POST'])]
    public function updateSettings(Request $request): RedirectResponse
    {
        $input = AccountRequestMapper::settings($request);

        $this->attempt(
            fn () => $this->accounts->updateSettings($this->currentUser(), $input),
            AccountFlash::SETTINGS_SAVED->text(),
        );

        return $this->redirect($this->generateUrl('account_settings') . '#e-mails');
    }

    #[Route('/account/export', name: 'account_export', methods: ['GET'])]
    public function export(): Response
    {
        return DownloadHelper::response($this->exports->build($this->currentUser()), $this->exports->fileName());
    }

    #[Csrf('account')]
    #[Route('/account/delete', name: 'account_delete', methods: ['POST'])]
    public function delete(Request $request): Response
    {
        $user = $this->currentUser();
        $input = AccountRequestMapper::deletion($request);

        if ($this->invalid($input) || !$this->attempt(fn () => $this->accounts->delete($user))) {
            return $this->redirect($this->generateUrl('account_settings') . '#supprimer');
        }

        $this->logoutUser();

        return $this->redirectToRoute('home_index');
    }
}