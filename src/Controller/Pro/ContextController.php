<?php

namespace App\Controller\Pro;

use App\Company\Enum\ContextFlash;
use App\Company\Mapper\ContextRequestMapper;
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
    #[Route(
        '/pro/establishments/{id}/switch',
        name: 'pro_establishment_switch',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function switch(Request $request, int $id): RedirectResponse
    {
        $establishment = $this->pro->switchTo($this->currentMember(), $id);
        $this->addFlash('success', ContextFlash::SWITCHED->text($establishment->getName()));
        $target = ContextRequestMapper::target($request);

        if ($target === null) {
            return $this->redirectToRoute('pro_dashboard');
        }

        return $this->redirect($target);
    }
}