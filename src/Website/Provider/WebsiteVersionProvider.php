<?php

namespace App\Website\Provider;

use App\Entity\Website\WebsiteConfig;
use App\Website\Exception\WebsiteVersionNotFoundException;

class WebsiteVersionProvider
{
    public function __construct(
        private WebsiteProvider $website,
    ) {
    }

    public function find(?string $code): WebsiteConfig
    {
        $version = $this->website->getVersion($code);

        if ($version === null) {
            throw new WebsiteVersionNotFoundException($code);
        }

        return $version;
    }
}