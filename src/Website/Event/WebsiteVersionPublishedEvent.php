<?php

namespace App\Website\Event;

use App\Entity\Website\WebsiteConfig;

class WebsiteVersionPublishedEvent
{
    public function __construct(
        private WebsiteConfig $website,
        private ?WebsiteConfig $previous = null,
        private bool $wasScheduled = false,
    ) {
    }

    public function getWebsite(): WebsiteConfig
    {
        return $this->website;
    }

    public function getPrevious(): ?WebsiteConfig
    {
        return $this->previous;
    }

    public function wasScheduled(): bool
    {
        return $this->wasScheduled;
    }
}