<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Establishment\Mapper\AgendaRequestMapper;
use App\Establishment\Provider\AgendaPageProvider;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class AgendaController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private AgendaPageProvider $pages,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route('/pro/agenda', name: 'pro_agenda', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $member = $this->currentMember();
        $establishment = $this->permittedEstablishment(Permission::PLANNING_VIEW);
        $page = $this->pages->index($member, $establishment, AgendaRequestMapper::week($request));

        return $this->renderPro('pro/agenda/index.html.twig', $page);
    }
}