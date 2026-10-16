<?php

namespace App\Website\Presenter;

use App\Entity\Website\WebsiteConfig;
use App\Shared\Helper\DateHelper;
use App\Website\Helper\WebsiteVersionHelper;

class WebsiteConfigPresenter
{
    private const IDENTITY = [
        'siteName',
        'domain',
        'tagline',
        'siteDescription',
        'seoTitle',
        'contactEmail'
    ];

    public function summary(WebsiteConfig $website): array
    {
        return [
            'id' => $website->getId(),
            'code' => $website->getCode(),
            'name' => $website->getName(),
            'description' => $website->getDescription(),
            'status' => $website->getStatus()->value,
            'statusLabel' => $website->getStatus()->label(),
            'editable' => WebsiteVersionHelper::isEditable($website),
            'scheduledAt' => DateHelper::iso($website->getScheduledAt()),
            'publishedAt' => DateHelper::iso($website->getPublishedAt()),
            'archivedAt' => DateHelper::iso($website->getArchivedAt()),
            'pricingName' => $website->getPricingName(),
            'parent' => $website->getParent()?->getCode(),
            'createdBy' => $website->getCreatedBy()?->getFirstName(),
            'createdAt' => DateHelper::iso($website->getCreatedAt()),
            'updatedAt' => DateHelper::iso($website->getUpdatedAt()),
        ];
    }

    public function detail(WebsiteConfig $website, ?WebsiteConfig $live): array
    {
        $compared = $live;

        if ($live === $website) {
            $compared = null;
        }

        $identity = [];

        foreach (self::IDENTITY as $field) {
            $identity[$field] = $this->identityField($website, $compared, $field);
        }

        return $this->summary($website) + ['identity' => $identity];
    }

    public function summaries(array $websites): array
    {
        return array_map(fn (WebsiteConfig $website): array => $this->summary($website), $websites);
    }

    private function identityField(WebsiteConfig $website, ?WebsiteConfig $live, string $field): array
    {
        $getter = 'get' . ucfirst($field);
        $value = $website->{$getter}();

        if ($live === null) {
            return ['value' => $value, 'liveValue' => null, 'changed' => false];
        }

        $liveValue = $live->{$getter}();

        return [
            'value' => $value,
            'liveValue' => $liveValue,
            'changed' => $value !== $liveValue
        ];
    }
}