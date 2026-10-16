<?php

namespace App\Website\Provider;

use App\Entity\Website\WebsiteConfig;
use App\Entity\Website\WebsiteRule;
use App\Repository\Website\WebsiteConfigRepository;
use App\Repository\Website\WebsitePlanRepository;
use App\Website\Exception\NoLiveWebsiteException;

class WebsiteProvider
{
    private ?WebsiteConfig $live = null;

    public function __construct(
        private readonly WebsiteConfigRepository $websites,
        private readonly WebsitePlanRepository $plans,
    ) {
    }

    public function getLive(): WebsiteConfig
    {
        return $this->live ??= $this->websites->findLive() ?? throw new NoLiveWebsiteException();
    }

    public function getRule(): WebsiteRule
    {
        return $this->getLive()->getRule() ?? throw new NoLiveWebsiteException();
    }

    public function getPlans(): array
    {
        return $this->plans->findActiveByWebsite($this->getLive());
    }

    public function getVersion(?string $code): ?WebsiteConfig
    {
        if ($code === null || $code === '') {
            return $this->websites->findLive();
        }

        return $this->websites->findOneByCode($code);
    }

    public function reset(): void
    {
        $this->live = null;
    }
}