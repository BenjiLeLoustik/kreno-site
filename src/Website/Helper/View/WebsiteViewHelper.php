<?php

namespace App\Website\Helper\View;

use App\Website\Enum\WebsiteDefault;
use App\Website\Exception\NoLiveWebsiteException;
use App\Website\Provider\WebsiteProvider;
use NeoPHP\Component\View\Contract\ViewFunctionInterface;

class WebsiteViewHelper implements ViewFunctionInterface
{
    private ?array $identity = null;

    public function __construct(
        private WebsiteProvider $provider,
    ) {
    }

    public function getName(): string
    {
        return 'website';
    }

    public function __invoke(): array
    {
        return $this->identity ??= $this->load();
    }

    private function load(): array
    {
        try {
            $live = $this->provider->getLive();
        } catch (NoLiveWebsiteException) {
            return $this->defaults();
        }

        return [
            'siteName' => $live->getSiteName() ?: WebsiteDefault::SITE_NAME->text(),
            'domain' => $live->getDomain(),
            'tagline' => $live->getTagline() ?: WebsiteDefault::TAGLINE->text(),
            'siteDescription' => $live->getSiteDescription() ?: WebsiteDefault::SITE_DESCRIPTION->text(),
            'seoTitle' => $live->getSeoTitle() ?: null,
            'contactEmail' => $live->getContactEmail() ?: null,
        ];
    }

    private function defaults(): array
    {
        return [
            'siteName' => WebsiteDefault::SITE_NAME->text(),
            'domain' => null,
            'tagline' => WebsiteDefault::TAGLINE->text(),
            'siteDescription' => WebsiteDefault::SITE_DESCRIPTION->text(),
            'seoTitle' => null,
            'contactEmail' => null,
        ];
    }
}