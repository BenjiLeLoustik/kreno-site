<?php

declare(strict_types=1);

namespace App\Entity\Website;

use App\Entity\AbstractBaseEntity;
use App\Entity\User\User;
use App\Repository\Website\WebsiteConfigRepository;
use App\Website\Enum\PriceChangePolicy;
use App\Website\Enum\WebsiteStatus;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: WebsiteConfigRepository::class)]
#[ORM\Index(columns: ['status'])]
class WebsiteConfig extends AbstractBaseEntity
{
    #[ORM\Column(length: 10, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 120)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 80)]
    private ?string $siteName = null;

    #[ORM\Column(length: 120)]
    private ?string $domain = null;

    #[ORM\Column(length: 255)]
    private ?string $tagline = null;

    #[ORM\Column(type: 'text')]
    private ?string $siteDescription = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $seoTitle = null;

    #[ORM\Column(length: 180)]
    private ?string $contactEmail = null;

    #[ORM\Column]
    private WebsiteStatus $status = WebsiteStatus::DRAFT;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $publishedAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $archivedAt = null;

    #[ORM\Column(length: 120)]
    private ?string $pricingName = null;

    #[ORM\Column]
    private PriceChangePolicy $priceChangePolicy = PriceChangePolicy::KEEP_UNTIL_RENEWAL;

    #[ORM\Column(type: 'smallint')]
    private int $priceChangeNoticeDays = 30;

    #[ORM\ManyToOne(WebsiteConfig::class, onDelete: 'SET NULL')]
    private ?WebsiteConfig $parent = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\OneToOne(WebsiteRule::class, mappedBy: 'website', cascade: ['persist', 'remove'])]
    private ?WebsiteRule $rule = null;

    #[ORM\OneToMany(WebsitePlan::class, mappedBy: 'website', cascade: ['persist', 'remove'], orphanRemoval: true, orderBy: ['position' => 'ASC'])]
    private CollectionInterface $plans;

    #[ORM\OneToMany(WebsitePromoCode::class, mappedBy: 'website', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $promoCodes;

    public function __construct()
    {
        $this->plans = new ArrayCollection();
        $this->promoCodes = new ArrayCollection();
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

    public function getSiteName(): ?string
    {
        return $this->siteName;
    }

    public function setSiteName(string $siteName): static
    {
        $this->siteName = $siteName;

        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    public function getTagline(): ?string
    {
        return $this->tagline;
    }

    public function setTagline(string $tagline): static
    {
        $this->tagline = $tagline;

        return $this;
    }

    public function getSiteDescription(): ?string
    {
        return $this->siteDescription;
    }

    public function setSiteDescription(string $siteDescription): static
    {
        $this->siteDescription = $siteDescription;

        return $this;
    }

    public function getSeoTitle(): ?string
    {
        return $this->seoTitle;
    }

    public function setSeoTitle(?string $seoTitle): static
    {
        $this->seoTitle = $seoTitle;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getStatus(): WebsiteStatus
    {
        return $this->status;
    }

    public function setStatus(WebsiteStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getScheduledAt(): ?DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getArchivedAt(): ?DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?DateTimeImmutable $archivedAt): static
    {
        $this->archivedAt = $archivedAt;

        return $this;
    }

    public function getPricingName(): ?string
    {
        return $this->pricingName;
    }

    public function setPricingName(string $pricingName): static
    {
        $this->pricingName = $pricingName;

        return $this;
    }

    public function getPriceChangePolicy(): PriceChangePolicy
    {
        return $this->priceChangePolicy;
    }

    public function setPriceChangePolicy(PriceChangePolicy $priceChangePolicy): static
    {
        $this->priceChangePolicy = $priceChangePolicy;

        return $this;
    }

    public function getPriceChangeNoticeDays(): int
    {
        return $this->priceChangeNoticeDays;
    }

    public function setPriceChangeNoticeDays(int $priceChangeNoticeDays): static
    {
        $this->priceChangeNoticeDays = $priceChangeNoticeDays;

        return $this;
    }

    public function getParent(): ?WebsiteConfig
    {
        return $this->parent;
    }

    public function setParent(?WebsiteConfig $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getRule(): ?WebsiteRule
    {
        return $this->rule;
    }

    public function setRule(?WebsiteRule $rule): static
    {
        $this->rule = $rule;

        if ($rule !== null && $rule->getWebsite() !== $this) {
            $rule->setWebsite($this);
        }

        return $this;
    }

    public function getPlans(): CollectionInterface
    {
        return $this->plans;
    }

    public function addPlan(WebsitePlan $plan): static
    {
        if (!$this->plans->contains($plan)) {
            $this->plans->add($plan);
            $plan->setWebsite($this);
        }

        return $this;
    }

    public function removePlan(WebsitePlan $plan): static
    {
        if ($this->plans->removeElement($plan) && $plan->getWebsite() === $this) {
            $plan->setWebsite(null);
        }

        return $this;
    }

    public function getPromoCodes(): CollectionInterface
    {
        return $this->promoCodes;
    }

    public function addPromoCode(WebsitePromoCode $promoCode): static
    {
        if (!$this->promoCodes->contains($promoCode)) {
            $this->promoCodes->add($promoCode);
            $promoCode->setWebsite($this);
        }

        return $this;
    }

    public function removePromoCode(WebsitePromoCode $promoCode): static
    {
        if ($this->promoCodes->removeElement($promoCode) && $promoCode->getWebsite() === $this) {
            $promoCode->setWebsite(null);
        }

        return $this;
    }
}