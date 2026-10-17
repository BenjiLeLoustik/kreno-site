<?php

namespace App\Website\Provider;

use App\Entity\Website\WebsiteConfig;
use App\Repository\Website\WebsiteConfigRepository;
use App\Website\Presenter\WebsiteConfigPresenter;
use App\Website\Presenter\WebsitePricingPresenter;
use App\Website\Presenter\WebsiteRulePresenter;

class AdminWebsitePageProvider
{
    public function __construct(
        private WebsiteConfigRepository $websites,
        private WebsiteConfigPresenter $presenter,
        private WebsiteRulePresenter $rulePresenter,
        private WebsitePricingPresenter $pricingPresenter,
    ) {
    }

    public function page(WebsiteConfig $website): array
    {
        $live = $this->websites->findLive();

        return [
            'versions' => $this->presenter->summaries($this->websites->findAllForAdmin()),
            'version' => $this->presenter->detail($website, $live),
            'rules' => $this->rulePresenter->present($website->getRule(), $live?->getRule()),
            'pricing' => $this->pricingPresenter->present($website, $live),
        ];
    }
}