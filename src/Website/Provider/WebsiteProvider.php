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
        private WebsiteConfigRepository $websites,
        private WebsitePlanRepository $plans,
    ) {
    }

    public function getLive(): WebsiteConfig
    {
        if ($this->live !== null) {
            return $this->live;
        }

        $live = $this->websites->findLive();

        if ($live === null) {
            throw new NoLiveWebsiteException();
        }

        return $this->live = $live;
    }

    public function getRule(): WebsiteRule
    {
        $rule = $this->getLive()->getRule();

        if ($rule === null) {
            throw new NoLiveWebsiteException();
        }

        return $rule;
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