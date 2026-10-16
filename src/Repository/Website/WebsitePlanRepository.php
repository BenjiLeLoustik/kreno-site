<?php

declare(strict_types=1);

namespace App\Repository\Website;

use App\Entity\Website\WebsiteConfig;
use App\Entity\Website\WebsitePlan;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class WebsitePlanRepository extends AbstractRepository
{
    protected string $entityClass = WebsitePlan::class;

    public function findOneByWebsiteAndCode(WebsiteConfig $website, string $code): ?WebsitePlan
    {
        return $this->findOneBy(['website' => $website, 'code' => $code]);
    }

    public function findActiveByWebsite(WebsiteConfig $website): array
    {
        return $this->findBy(['website' => $website, 'active' => true], ['position' => 'ASC']);
    }
}