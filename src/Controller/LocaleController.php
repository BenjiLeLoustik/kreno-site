<?php

namespace App\Controller;

use App\Shared\Mapper\LocaleRequestMapper;
use App\Shared\Service\LocaleService;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    public function __construct(
        private LocaleService $locales,
    ) {
    }

    #[Route(
        '/locale/{locale}',
        name: 'locale_switch',
        methods: ['GET'],
        requirements: ['locale' => '[a-zA-Z]{2}([_-][a-zA-Z]{2})?'],
    )]
    public function switchTo(Request $request, string $locale): RedirectResponse
    {
        $this->switchLocale($this->locales->enabled($locale));

        return new RedirectResponse(LocaleRequestMapper::target($request));
    }
}