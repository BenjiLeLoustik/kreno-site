<?php

namespace App\Website\Helper\View;

use App\Website\Exception\NoLiveWebsiteException;
use App\Website\Provider\WebsiteProvider;
use NeoPHP\Component\View\Contract\ViewFunctionInterface;

class WebsiteViewHelper implements ViewFunctionInterface
{
    private const DEFAULTS = [
        'siteName' => 'Kréno',
        'domain' => null,
        'tagline' => 'Un créneau vient de se libérer. Il est pour toi.',
        'siteDescription' => 'Les créneaux libérés de ton quartier, à prix réduit. Tu réserves en deux clics, tu paies sur place.',
        'seoTitle' => null,
        'contactEmail' => null,
    ];

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
            return self::DEFAULTS;
        }

        return [
            'siteName' => $live->getSiteName() ?: self::DEFAULTS['siteName'],
            'domain' => $live->getDomain(),
            'tagline' => $live->getTagline() ?: self::DEFAULTS['tagline'],
            'siteDescription' => $live->getSiteDescription() ?: self::DEFAULTS['siteDescription'],
            'seoTitle' => $live->getSeoTitle() ?: null,
            'contactEmail' => $live->getContactEmail() ?: null,
        ];
    }
}