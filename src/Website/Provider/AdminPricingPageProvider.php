<?php

namespace App\Website\Provider;

use App\Billing\Provider\BillingKpiProvider;
use App\Entity\Website\WebsiteConfig;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Repository\Website\WebsiteConfigRepository;
use App\Repository\Website\WebsitePlanRepository;
use App\Repository\Website\WebsitePromoCodeRepository;
use App\Website\Enum\PlanBilling;
use App\Website\Enum\PriceChangePolicy;
use App\Website\Enum\WebsiteStatus;
use App\Website\Exception\PlanNotFoundException;
use App\Website\Exception\PromoCodeNotFoundException;
use App\Website\Presenter\WebsiteConfigPresenter;
use App\Website\Presenter\WebsitePricingPresenter;

class AdminPricingPageProvider
{
    public function __construct(
        private WebsiteVersionProvider $versions,
        private WebsiteConfigRepository $websites,
        private WebsitePlanRepository $plans,
        private WebsitePromoCodeRepository $promoCodes,
        private WebsiteConfigPresenter $websitePresenter,
        private WebsitePricingPresenter $pricingPresenter,
        private BillingKpiProvider $kpis,
    ) {
    }

    public function index(?string $version): array
    {
        $website = $this->versions->find($version);

        return [
            'versions' => $this->websitePresenter->summaries($this->websites->findAllForAdmin()),
            'selected' => $this->websitePresenter->summary($website),
            'pricing' => $this->pricingPresenter->present($website, $this->websites->findLive()),
            'kpis' => $this->kpis($website),
            'billings' => $this->billings(),
            'policies' => $this->policies(),
        ];
    }

    public function plan(WebsiteConfig $website, string $code): WebsitePlan
    {
        $plan = $this->plans->findOneByWebsiteAndCode($website, $code);

        if ($plan === null) {
            throw new PlanNotFoundException($code);
        }

        return $plan;
    }

    public function promoCode(WebsiteConfig $website, string $code): WebsitePromoCode
    {
        $promo = $this->promoCodes->findOneByWebsiteAndCode($website, $code);

        if ($promo === null) {
            throw new PromoCodeNotFoundException($code);
        }

        return $promo;
    }

    private function kpis(WebsiteConfig $website): ?array
    {
        if ($website->getStatus() !== WebsiteStatus::LIVE) {
            return null;
        }

        return $this->kpis->pricing();
    }

    private function billings(): array
    {
        $billings = [];

        foreach (PlanBilling::cases() as $billing) {
            $billings[] = ['value' => $billing->value, 'label' => $billing->label()];
        }

        return $billings;
    }

    private function policies(): array
    {
        $policies = [];

        foreach (PriceChangePolicy::cases() as $policy) {
            $policies[] = ['value' => $policy->value, 'label' => $policy->label()];
        }

        return $policies;
    }
}