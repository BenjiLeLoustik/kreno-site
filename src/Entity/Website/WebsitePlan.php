<?php

declare(strict_types=1);

namespace App\Entity\Website;

use App\Entity\AbstractBaseEntity;
use App\Entity\Trait\PositionTrait;
use App\Repository\Website\WebsitePlanRepository;
use App\Website\Enum\PlanBilling;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: WebsitePlanRepository::class)]
#[ORM\Index(columns: ['website', 'code'], unique: true)]
class WebsitePlan extends AbstractBaseEntity
{
    use PositionTrait;

    #[ORM\ManyToOne(WebsiteConfig::class, inversedBy: 'plans', nullable: false, onDelete: 'CASCADE')]
    private ?WebsiteConfig $website = null;

    #[ORM\Column(length: 40)]
    private ?string $code = null;

    #[ORM\Column(length: 80)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private PlanBilling $billing = PlanBilling::SUBSCRIPTION;

    #[ORM\Column]
    private int $price = 0;

    #[ORM\Column(nullable: true)]
    private ?int $yearlyPrice = null;

    #[ORM\Column(type: 'smallint')]
    private int $vatRate = 20;

    #[ORM\Column(type: 'smallint')]
    private int $trialDays = 0;

    #[ORM\Column(type: 'smallint')]
    private int $commitmentMonths = 0;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $maxEstablishments = null;

    #[ORM\Column]
    private bool $commissionFree = false;

    #[ORM\Column]
    private bool $featured = false;

    #[ORM\Column]
    private bool $advancedStats = false;

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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getBilling(): PlanBilling
    {
        return $this->billing;
    }

    public function setBilling(PlanBilling $billing): static
    {
        $this->billing = $billing;

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getYearlyPrice(): ?int
    {
        return $this->yearlyPrice;
    }

    public function setYearlyPrice(?int $yearlyPrice): static
    {
        $this->yearlyPrice = $yearlyPrice;

        return $this;
    }

    public function getVatRate(): int
    {
        return $this->vatRate;
    }

    public function setVatRate(int $vatRate): static
    {
        $this->vatRate = $vatRate;

        return $this;
    }

    public function getTrialDays(): int
    {
        return $this->trialDays;
    }

    public function setTrialDays(int $trialDays): static
    {
        $this->trialDays = $trialDays;

        return $this;
    }

    public function getCommitmentMonths(): int
    {
        return $this->commitmentMonths;
    }

    public function setCommitmentMonths(int $commitmentMonths): static
    {
        $this->commitmentMonths = $commitmentMonths;

        return $this;
    }

    public function getMaxEstablishments(): ?int
    {
        return $this->maxEstablishments;
    }

    public function setMaxEstablishments(?int $maxEstablishments): static
    {
        $this->maxEstablishments = $maxEstablishments;

        return $this;
    }

    public function isCommissionFree(): bool
    {
        return $this->commissionFree;
    }

    public function setCommissionFree(bool $commissionFree): static
    {
        $this->commissionFree = $commissionFree;

        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): static
    {
        $this->featured = $featured;

        return $this;
    }

    public function isAdvancedStats(): bool
    {
        return $this->advancedStats;
    }

    public function setAdvancedStats(bool $advancedStats): static
    {
        $this->advancedStats = $advancedStats;

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