<?php

declare(strict_types=1);

namespace App\Repository\Website;

use App\Entity\Website\WebsiteConfig;
use App\Website\Enum\WebsiteStatus;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class WebsiteConfigRepository extends AbstractRepository
{
    protected string $entityClass = WebsiteConfig::class;

    public function findLive(): ?WebsiteConfig
    {
        return $this->findOneBy(['status' => WebsiteStatus::LIVE]);
    }

    public function findOneByCode(string $code): ?WebsiteConfig
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function findScheduled(): ?WebsiteConfig
    {
        return $this->findOneBy(['status' => WebsiteStatus::SCHEDULED]);
    }

    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('w')
            ->orderBy('w.createdAt', 'DESC')
            ->getResult();
    }

    public function findDueForPublication(DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.status = :status')
            ->andWhere('w.scheduledAt <= :now')
            ->setParameter('status', WebsiteStatus::SCHEDULED)
            ->setParameter('now', $now)
            ->orderBy('w.scheduledAt', 'ASC')
            ->getResult();
    }

    public function findAllCodes(): array
    {
        return array_map(static fn (WebsiteConfig $website): string => (string) $website->getCode(), $this->findAll());
    }
}