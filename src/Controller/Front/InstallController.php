<?php

namespace App\Controller\Front;

use App\Entity\User\User;
use App\Website\Enum\InstallFlash;
use App\Website\Mapper\InstallRequestMapper;
use App\Website\Provider\InstallPageProvider;
use App\Website\Service\WebsiteInstallService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class InstallController extends AbstractFrontController
{
    public function __construct(
        private WebsiteInstallService $installer,
        private InstallPageProvider $pages,
    ) {
    }

    #[Route('/install', name: 'app_install', methods: ['GET'])]
    public function form(): Response
    {
        if ($this->installer->isInstalled()) {
            return $this->redirectToRoute('home_index');
        }

        $needsAdmin = !$this->installer->hasAdmin();

        if (!$needsAdmin && !$this->isGranted('ROLE_ADMIN')) {
            return $this->render('install/login.html.twig', [
                'loggedIn' => $this->optionalUser() !== null,
            ]);
        }

        return $this->render('install/index.html.twig', $this->pages->form($needsAdmin));
    }

    #[Csrf('install')]
    #[Route('/install', name: 'app_install_submit', methods: ['POST'])]
    public function install(Request $request): RedirectResponse
    {
        if ($this->installer->isInstalled()) {
            return $this->redirectToRoute('home_index');
        }

        $needsAdmin = !$this->installer->hasAdmin();

        if (!$needsAdmin && !$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_install');
        }

        $identityValues = InstallRequestMapper::section($request, 'identity');
        $ruleValues = InstallRequestMapper::section($request, 'rules');
        $adminValues = InstallRequestMapper::section($request, 'admin');
        $planValues = InstallRequestMapper::planValues($request);

        $identity = InstallRequestMapper::identity($identityValues);
        $rules = InstallRequestMapper::rules($ruleValues);
        $plans = InstallRequestMapper::plans($planValues);
        $actor = $this->optionalUser();

        $done = !$this->invalid($identity)
            && !$this->invalid($rules)
            && !$this->invalidAll($plans)
            && $this->attempt(function () use ($identity, $rules, $plans, $needsAdmin, $adminValues, &$actor): void {
                $this->installer->assertInstallable($identity, $rules, $plans);

                if ($needsAdmin) {
                    $actor = $this->installer->createAdmin(...InstallRequestMapper::admin($adminValues));
                }

                $this->installer->install($identity, $rules, $plans, $actor);
            }, InstallFlash::DONE->text());

        if (!$done) {
            $this->pages->remember(InstallRequestMapper::old($identityValues, $ruleValues, $planValues, $adminValues));

            return $this->redirectToRoute('app_install');
        }

        if ($needsAdmin && $actor instanceof User) {
            $this->loginUser($actor);
        }

        return $this->redirectToRoute('admin_dashboard');
    }
}