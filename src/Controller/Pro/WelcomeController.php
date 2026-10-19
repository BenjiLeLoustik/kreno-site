<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Establishment\Provider\ProEntryProvider;
use App\Establishment\Provider\ProWelcomeProvider;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class WelcomeController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private ProEntryProvider $entry,
        private ProWelcomeProvider $welcome,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route('/pro/welcome', name: 'pro_welcome', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->currentUser();
        $establishment = $this->entry->welcomeEstablishment($user);

        if ($establishment === null) {
            return $this->redirectToRoute('pro_signup');
        }

        $this->requirePermission(Permission::PLANNING_VIEW, $establishment);

        return $this->renderPro('pro/welcome/index.html.twig', [
            'welcome' => $this->welcome->build($establishment, $user),
        ]);
    }
}