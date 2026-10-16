<?php

namespace App\Website\Helper;

use App\Entity\Website\WebsiteConfig;
use App\Website\Enum\WebsiteStatus;

class WebsiteVersionHelper
{
    public static function isEditable(WebsiteConfig $website): bool
    {
        return in_array($website->getStatus(), [WebsiteStatus::DRAFT, WebsiteStatus::SCHEDULED], true);
    }

    public static function nextCode(array $existingCodes): string
    {
        $max = 0;

        foreach ($existingCodes as $code) {
            if (preg_match('/^v(\d+)$/i', (string) $code, $matches) === 1) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return 'v' . ($max + 1);
    }
}