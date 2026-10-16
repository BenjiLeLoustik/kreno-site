<?php

namespace App\Controller\Admin;

use App\Entity\Website\WebsiteConfig;
use App\Log\Helper\ActivityGroupHelper;
use App\Log\Presenter\ActivityLogPresenter;
use App\Repository\ActivityLogRepository;
use App\Repository\Website\WebsiteConfigRepository;
use App\User\Presenter\StaffPresenter;
use App\User\Service\StaffService;
use App\Website\Dto\WebsiteRuleInput;
use App\Website\Presenter\WebsiteConfigPresenter;
use App\Website\Presenter\WebsiteRulePresenter;
use App\Website\Provider\WebsiteProvider;
use App\Website\Service\WebsiteRuleService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class SettingsController extends AbstractAdminController
{
    public const LOG_PAGE_SIZE = 30;

    public const DASHBOARD_LOG_SIZE = 8;

    public function __construct(
        private WebsiteProvider $provider,
        private WebsiteConfigRepository $websites,
        private WebsiteRuleService $rules,
        private ActivityLogRepository $logs,
        private StaffService $staff,
        private WebsiteConfigPresenter $websitePresenter,
        private WebsiteRulePresenter $rulePresenter,
        private ActivityLogPresenter $logPresenter,
        private StaffPresenter $staffPresenter,
    ) {
    }

    #[Route('/admin/settings', name: 'admin_settings_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $admin = $this->currentAdmin();

        $website = $this->findVersion($this->provider, $request->query->getString('version') ?: null);
        $live = $this->websites->findLive();

        return $this->render('admin/settings/index.html.twig', [
            'versions' => $this->websitePresenter->summaries($this->websites->findAllForAdmin()),
            'selected' => $this->websitePresenter->summary($website),
            'rules' => $this->rulePresenter->present($website->getRule(), $live?->getRule()),
            'members' => $this->staffPresenter->presentAll($this->staff->members(), $admin),
            'journal' => $this->journal($request, null, self::DASHBOARD_LOG_SIZE),
        ]);
    }

    #[Route('/admin/log', name: 'admin_log', methods: ['GET'])]
    public function log(Request $request): Response
    {
        $this->currentAdmin();

        $code = $request->query->getString('version');

        return $this->render('admin/settings/log.html.twig', [
            'versions' => $this->websitePresenter->summaries($this->websites->findAllForAdmin()),
            'journal' => $this->journal($request, $code !== '' ? $this->findVersion($this->provider, $code) : null, self::LOG_PAGE_SIZE),
        ]);
    }

    #[Csrf('admin_settings')]
    #[Route('/admin/settings/{code}', name: 'admin_settings_update', methods: ['POST'])]
    public function update(string $code, #[MapRequestPayload(validate: false)] WebsiteRuleInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->findVersion($this->provider, $code);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->rules->update($website, $input, $admin), sprintf('Règles de la version %s enregistrées.', $website->getCode()));
        }

        return $this->redirectToRoute('admin_settings_index', ['version' => $website->getCode()]);
    }

    private function journal(Request $request, ?WebsiteConfig $website, int $size): array
    {
        $group = ActivityGroupHelper::normalize($request->query->getString('group') ?: null);
        $search = trim($request->query->getString('q'));
        $page = max(1, $request->query->getInt('page', 1));
        $types = ActivityGroupHelper::types($group);
        $total = $this->logs->countForAdmin($types, $website, $search !== '' ? $search : null);

        return [
            'group' => $group,
            'filters' => ActivityGroupHelper::filters($group),
            'q' => $search,
            'version' => $website?->getCode(),
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $size)),
            'total' => $total,
            'entries' => $this->logPresenter->presentAll($this->logs->findForAdmin($types, $website, $search !== '' ? $search : null, $size, ($page - 1) * $size)),
        ];
    }
}