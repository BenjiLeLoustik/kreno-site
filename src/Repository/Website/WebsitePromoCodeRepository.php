<?php

declare(strict_types=1);

namespace App\Repository\Website;

use App\Entity\Website\WebsiteConfig;
use App\Entity\Website\WebsitePromoCode;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class WebsitePromoCodeRepository extends AbstractRepository
{
    protected string $entityClass = WebsitePromoCode::class;

    public function findOneByWebsiteAndCode(WebsiteConfig $website, string $code): ?WebsitePromoCode
    {
        return $this->findOneBy(['website' => $website, 'code' => strtoupper(trim($code))]);
    }
}