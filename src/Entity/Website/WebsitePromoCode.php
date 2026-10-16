<?php

declare(strict_types=1);

namespace App\Entity\Website;

use App\Entity\AbstractBaseEntity;
use App\Repository\Website\WebsitePromoCodeRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: WebsitePromoCodeRepository::class)]
#[ORM\Index(columns: ['website', 'code'], unique: true)]
class WebsitePromoCode extends AbstractBaseEntity
{
    #[ORM\ManyToOne(WebsiteConfig::class, inversedBy: 'promoCodes', nullable: false, onDelete: 'CASCADE')]
    private ?WebsiteConfig $website = null;

    #[ORM\ManyToOne(WebsitePlan::class, onDelete: 'SET NULL')]
    private ?WebsitePlan $plan = null;

    #[ORM\Column(length: 40)]
    private ?string $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'smallint')]
    private int $discountPercent = 0;

    #[ORM\Column(type: 'smallint')]
    private int $freeMonths = 0;

    #[ORM\Column(type: 'smallint')]
    private int $durationMonths = 1;

    #[ORM\Column(nullable: true)]
    private ?int $maxUses = null;

    #[ORM\Column]
    private int $usesCount = 0;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $startsAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $endsAt = null;

    #[ORM\Column]
    private bool $active = true;

    public function getWebsite(): ?WebsiteConfig
    {
        return $this->website;
    }

    public function setWebsite(?WebsiteConfig $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getPlan(): ?WebsitePlan
    {
        return $this->plan;
    }

    public function setPlan(?WebsitePlan $plan): static
    {
        $this->plan = $plan;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDiscountPercent(): int
    {
        return $this->discountPercent;
    }

    public function setDiscountPercent(int $discountPercent): static
    {
        $this->discountPercent = $discountPercent;

        return $this;
    }

    public function getFreeMonths(): int
    {
        return $this->freeMonths;
    }

    public function setFreeMonths(int $freeMonths): static
    {
        $this->freeMonths = $freeMonths;

        return $this;
    }

    public function getDurationMonths(): int
    {
        return $this->durationMonths;
    }

    public function setDurationMonths(int $durationMonths): static
    {
        $this->durationMonths = $durationMonths;

        return $this;
    }

    public function getMaxUses(): ?int
    {
        return $this->maxUses;
    }

    public function setMaxUses(?int $maxUses): static
    {
        $this->maxUses = $maxUses;

        return $this;
    }

    public function getUsesCount(): int
    {
        return $this->usesCount;
    }

    public function setUsesCount(int $usesCount): static
    {
        $this->usesCount = $usesCount;

        return $this;
    }

    public function getStartsAt(): ?DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(?DateTimeImmutable $startsAt): static
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): ?DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(?DateTimeImmutable $endsAt): static
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }
}