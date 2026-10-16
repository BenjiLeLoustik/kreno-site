<?php

namespace App\Controller\Front;

use App\Booking\Provider\CustomerDashboardProvider;
use App\Shared\Helper\DownloadHelper;
use App\User\Dto\ChangePasswordInput;
use App\User\Dto\DeleteAccountInput;
use App\User\Dto\ProfileInput;
use App\User\Dto\SettingsInput;
use App\User\Presenter\AlertPresenter;
use App\User\Presenter\UserPresenter;
use App\User\Service\AccountService;
use App\User\Service\DataExportService;
use App\User\Service\PasswordService;
use App\Website\Provider\WebsiteProvider;
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
        private CustomerDashboardProvider $dashboard,
        private WebsiteProvider $website,
        private UserPresenter $presenter,
        private AlertPresenter $alertPresenter,
    ) {
    }

    #[Route('/account', name: 'account_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->currentUser();

        return $this->render('front/account/index.html.twig', [
            'user' => $this->presenter->profile($user),
            'dashboard' => $this->dashboard->build($user),
            'alerts' => $this->alertPresenter->presentAll($user->getAlerts()->toArray()),
            'rules' => $this->customerRules(),
        ]);
    }

    #[Csrf('account')]
    #[Route('/account/profile', name: 'account_profile', methods: ['POST'])]
    public function profile(Request $request): RedirectResponse
    {
        $input = new ProfileInput();
        $input->firstName = trim($request->request->getString('firstName'));
        $input->lastName = trim($request->request->getString('lastName'));
        $input->phone = trim($request->request->getString('phone'));
        $input->email = trim($request->request->getString('email'));

        if ($input->firstName === '') {
            $this->addFlash('error', 'Indiquez votre prénom.');
        } elseif ($input->email === '') {
            $this->addFlash('error', 'Indiquez votre adresse e-mail.');
        } elseif (!$this->invalid($input)) {
            $this->attempt(fn () => $this->accounts->updateProfile($this->currentUser(), $input), 'Informations enregistrées.');
        }

        return $this->redirect($this->generateUrl('account_settings') . '#informations');
    }

    #[Csrf('account')]
    #[Route('/account/password', name: 'account_password', methods: ['POST'])]
    public function password(Request $request): RedirectResponse
    {
        $input = new ChangePasswordInput();
        $input->currentPassword = $request->request->getString('currentPassword');
        $input->newPassword = $request->request->getString('newPassword');

        if ($input->newPassword !== $request->request->getString('confirmPassword')) {
            $this->addFlash('error', 'Les deux mots de passe ne correspondent pas.');
        } elseif (!$this->invalid($input)) {
            $this->attempt(fn () => $this->passwords->change($this->currentUser(), $input), 'Mot de passe modifié.');
        }

        return $this->redirect($this->generateUrl('account_settings') . '#mot-de-passe');
    }

    #[Route('/account/settings', name: 'account_settings', methods: ['GET'])]
    public function settings(): Response
    {
        return $this->render('front/account/settings.html.twig', [
            'profile' => $this->presenter->profile($this->currentUser()),
        ]);
    }

    #[Csrf('account')]
    #[Route('/account/settings', name: 'account_settings_update', methods: ['POST'])]
    public function updateSettings(Request $request): RedirectResponse
    {
        $input = new SettingsInput();
        $input->emailNotifications = $request->request->getBoolean('emailNotifications');
        $this->attempt(fn () => $this->accounts->updateSettings($this->currentUser(), $input), 'Préférences enregistrées.');

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
        $input = new DeleteAccountInput();
        $input->confirmation = $request->request->getString('confirmation');

        if ($this->invalid($input) || !$this->attempt(fn () => $this->accounts->delete($user))) {
            return $this->redirect($this->generateUrl('account_settings') . '#supprimer');
        }

        $this->logoutUser();

        return $this->redirectToRoute('home_index');
    }

    private function customerRules(): array
    {
        $rule = $this->website->getRule();

        return [
            'freeCancellationMinutes' => $rule->getFreeCancellationMinutes(),
            'maxUpcomingBookings' => $rule->getMaxUpcomingBookingsPerUser(),
            'toleratedNoShows' => $rule->getToleratedNoShows(),
            'noShowWindowDays' => $rule->getNoShowWindowDays(),
            'suspensionDays' => $rule->getSuspensionDays(),
        ];
    }
}