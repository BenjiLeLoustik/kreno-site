<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Establishment\Provider\ProWelcomeProvider;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class WelcomeController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        private ProWelcomeProvider $welcome,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Route('/pro/welcome', name: 'pro_welcome', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->currentUser();
        $member = $this->context->getMembership($user);
        $establishment = $member !== null ? $this->context->getCurrentEstablishment($member) : null;

        if ($establishment === null) {
            return $this->redirectToRoute('pro_signup');
        }

        $this->requirePermission(Permission::PLANNING_VIEW, $establishment);

        return $this->render('pro/welcome/index.html.twig', [
            'context' => $this->proContext(),
            'welcome' => $this->welcome->build($establishment, $user),
        ]);
    }
}