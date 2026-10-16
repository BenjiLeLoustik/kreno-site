<?php

namespace App\Website\Event;

use App\Entity\Website\WebsiteConfig;

class WebsiteVersionScheduledEvent
{
    public function __construct(
        private WebsiteConfig $website,
    ) {
    }

    public function getWebsite(): WebsiteConfig
    {
        return $this->website;
    }
}