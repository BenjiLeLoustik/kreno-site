<?php

namespace App\Controller\Admin;

use App\Log\Mapper\ActivityJournalRequestMapper;
use App\Shared\Mapper\AdminQueryMapper;
use App\Website\Dto\WebsiteRuleInput;
use App\Website\Enum\SettingsFlash;
use App\Website\Provider\AdminSettingsPageProvider;
use App\Website\Provider\WebsiteVersionProvider;
use App\Website\Service\WebsiteRuleService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class SettingsController extends AbstractAdminController
{
    public function __construct(
        private WebsiteVersionProvider $versions,
        private WebsiteRuleService $rules,
        private AdminSettingsPageProvider $pages,
    ) {
    }

    #[Route('/admin/settings', name: 'admin_settings_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $admin = $this->currentAdmin();
        $page = $this->pages->index(
            AdminQueryMapper::optional($request, 'version'),
            $admin,
            ActivityJournalRequestMapper::query($request),
        );

        return $this->render('admin/settings/index.html.twig', $page);
    }

    #[Route('/admin/log', name: 'admin_log', methods: ['GET'])]
    public function log(Request $request): Response
    {
        $this->currentAdmin();
        $page = $this->pages->log(
            AdminQueryMapper::optional($request, 'version'),
            ActivityJournalRequestMapper::query($request),
        );

        return $this->render('admin/settings/log.html.twig', $page);
    }

    #[Csrf('admin_settings')]
    #[Route('/admin/settings/{code}', name: 'admin_settings_update', methods: ['POST'])]
    public function update(
        string $code,
        #[MapRequestPayload(validate: false)] WebsiteRuleInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin();
        $website = $this->versions->find($code);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->rules->update($website, $input, $admin),
                SettingsFlash::RULES_SAVED->text($website->getCode()),
            );
        }

        return $this->redirectToRoute('admin_settings_index', ['version' => $website->getCode()]);
    }
}