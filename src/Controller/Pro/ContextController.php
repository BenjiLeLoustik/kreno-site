<?php

namespace App\Controller\Pro;

use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\JsonResponse;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Routing\Attribute\Route;

class ContextController extends AbstractProController
{
    #[Route('/pro/context', name: 'pro_context', methods: ['GET'])]
    public function show(): JsonResponse
    {
        return $this->json($this->proContext());
    }

    #[Csrf('pro')]
    #[Route('/pro/establishments/{id}/switch', name: 'pro_establishment_switch', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function switch(Request $request, int $id): RedirectResponse
    {
        $establishment = $this->context->switchTo($this->currentMember(), $id) ?? throw $this->createNotFoundException('Établissement inaccessible.');
        $this->addFlash('success', sprintf('Vous gérez maintenant %s.', $establishment->getName()));
        $target = $request->request->getString('_target');

        return str_starts_with($target, '/pro') && !str_starts_with($target, '//') ? $this->redirect($target) : $this->redirectToRoute('pro_dashboard');
    }
}