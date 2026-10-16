<?php

namespace App\Entity\Company;

use App\Entity\AbstractBaseEntity;
use App\Entity\Website\WebsiteConfig;
use App\Repository\Company\CompanyPriceNoticeRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CompanyPriceNoticeRepository::class)]
#[ORM\Index(columns: ['subscription', 'website'], unique: true)]
#[ORM\Index(columns: ['effectiveAt'])]
class CompanyPriceNotice extends AbstractBaseEntity
{
    #[ORM\ManyToOne(CompanySubscription::class, nullable: false, onDelete: 'CASCADE')]
    private ?CompanySubscription $subscription = null;

    #[ORM\ManyToOne(WebsiteConfig::class, nullable: false, onDelete: 'CASCADE')]
    private ?WebsiteConfig $website = null;

    #[ORM\Column]
    private int $currentPrice = 0;

    #[ORM\Column]
    private int $newPrice = 0;

    #[ORM\Column]
    private ?DateTimeImmutable $effectiveAt = null;

    public function getSubscription(): ?CompanySubscription
    {
        return $this->subscription;
    }

    public function setSubscription(CompanySubscription $subscription): static
    {
        $this->subscription = $subscription;

        return $this;
    }

    public function getWebsite(): ?WebsiteConfig
    {
        return $this->website;
    }

    public function setWebsite(WebsiteConfig $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getCurrentPrice(): int
    {
        return $this->currentPrice;
    }

    public function setCurrentPrice(int $currentPrice): static
    {
        $this->currentPrice = $currentPrice;

        return $this;
    }

    public function getNewPrice(): int
    {
        return $this->newPrice;
    }

    public function setNewPrice(int $newPrice): static
    {
        $this->newPrice = $newPrice;

        return $this;
    }

    public function getEffectiveAt(): ?DateTimeImmutable
    {
        return $this->effectiveAt;
    }

    public function setEffectiveAt(DateTimeImmutable $effectiveAt): static
    {
        $this->effectiveAt = $effectiveAt;

        return $this;
    }
}