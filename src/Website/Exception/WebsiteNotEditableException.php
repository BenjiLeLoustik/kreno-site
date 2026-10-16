<?php

namespace App\Website\Exception;

use App\Entity\Website\WebsiteConfig;
use App\Shared\Exception\BusinessRuleException;

class WebsiteNotEditableException extends BusinessRuleException
{
    public function __construct(WebsiteConfig $website)
    {
        parent::__construct('La version {code} est {status} : elle ne peut plus être modifiée. Créez une nouvelle version à partir d\'elle.', [
            'code' => $website->getCode(),
            'status' => mb_strtolower($website->getStatus()->label()),
        ]);
    }
}