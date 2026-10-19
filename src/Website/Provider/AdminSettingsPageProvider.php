<?php

namespace App\Website\Provider;

use App\Entity\User\User;
use App\Log\Provider\ActivityJournalProvider;
use App\Repository\Website\WebsiteConfigRepository;
use App\User\Presenter\StaffPresenter;
use App\User\Service\StaffService;
use App\Website\Presenter\WebsiteConfigPresenter;
use App\Website\Presenter\WebsiteRulePresenter;

class AdminSettingsPageProvider
{
    public const LOG_PAGE_SIZE = 30;

    public const DASHBOARD_LOG_SIZE = 8;

    public function __construct(
        private WebsiteVersionProvider $versions,
        private WebsiteConfigRepository $websites,
        private StaffService $staff,
        private ActivityJournalProvider $journal,
        private WebsiteConfigPresenter $websitePresenter,
        private WebsiteRulePresenter $rulePresenter,
        private StaffPresenter $staffPresenter,
    ) {
    }

    public function index(?string $version, User $admin, array $journalQuery): array
    {
        $website = $this->versions->find($version);

        return [
            'versions' => $this->websitePresenter->summaries($this->websites->findAllForAdmin()),
            'selected' => $this->websitePresenter->summary($website),
            'rules' => $this->rulePresenter->present($website->getRule(), $this->websites->findLive()?->getRule()),
            'members' => $this->staffPresenter->presentAll($this->staff->members(), $admin),
            'journal' => $this->journal->journal($journalQuery, null, self::DASHBOARD_LOG_SIZE),
        ];
    }

    public function log(?string $version, array $journalQuery): array
    {
        $website = null;

        if ($version !== null) {
            $website = $this->versions->find($version);
        }

        return [
            'versions' => $this->websitePresenter->summaries($this->websites->findAllForAdmin()),
            'journal' => $this->journal->journal($journalQuery, $website, self::LOG_PAGE_SIZE),
        ];
    }
}