<?php

declare(strict_types=1);

namespace App\Repository\Website;

use App\Entity\Website\WebsiteRule;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class WebsiteRuleRepository extends AbstractRepository
{
    protected string $entityClass = WebsiteRule::class;
}