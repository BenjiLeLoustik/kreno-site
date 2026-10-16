<?php

declare(strict_types=1);

namespace App\Entity\Company;

use App\Billing\Enum\BillingPeriod;
use App\Billing\Enum\SubscriptionStatus;
use App\Entity\AbstractBaseEntity;
use App\Entity\User\User;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Repository\Company\CompanySubscriptionRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CompanySubscriptionRepository::class)]
#[ORM\Index(columns: ['company', 'status'])]
#[ORM\Index(columns: ['status', 'currentPeriodEnd'])]
class CompanySubscription extends AbstractBaseEntity
{
    #[ORM\ManyToOne(Company::class, inversedBy: 'subscriptions', nullable: false, onDelete: 'CASCADE')]
    private ?Company $company = null;

    #[ORM\ManyToOne(WebsitePlan::class, nullable: false)]
    private ?WebsitePlan $plan = null;

    #[ORM\ManyToOne(WebsitePromoCode::class, onDelete: 'SET NULL')]
    private ?WebsitePromoCode $promoCode = null;

    #[ORM\ManyToOne(CompanySubscription::class, onDelete: 'SET NULL')]
    private ?CompanySubscription $previous = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $changedBy = null;

    #[ORM\Column]
    private BillingPeriod $period = BillingPeriod::MONTHLY;

    #[ORM\Column]
    private SubscriptionStatus $status = SubscriptionStatus::PENDING;

    #[ORM\Column]
    private int $unitPrice = 0;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $promoEndsAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $currentPeriodStart = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $currentPeriodEnd = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $cancelAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $canceledAt = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $cancellationReason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $cancellationComment = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $pausedUntil = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $graceUntil = null;

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

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

    public function getPromoCode(): ?WebsitePromoCode
    {
        return $this->promoCode;
    }

    public function setPromoCode(?WebsitePromoCode $promoCode): static
    {
        $this->promoCode = $promoCode;

        return $this;
    }

    public function getPrevious(): ?CompanySubscription
    {
        return $this->previous;
    }

    public function setPrevious(?CompanySubscription $previous): static
    {
        $this->previous = $previous;

        return $this;
    }

    public function getChangedBy(): ?User
    {
        return $this->changedBy;
    }

    public function setChangedBy(?User $changedBy): static
    {
        $this->changedBy = $changedBy;

        return $this;
    }

    public function getPeriod(): BillingPeriod
    {
        return $this->period;
    }

    public function setPeriod(BillingPeriod $period): static
    {
        $this->period = $period;

        return $this;
    }

    public function getStatus(): SubscriptionStatus
    {
        return $this->status;
    }

    public function setStatus(SubscriptionStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getPromoEndsAt(): ?DateTimeImmutable
    {
        return $this->promoEndsAt;
    }

    public function setPromoEndsAt(?DateTimeImmutable $promoEndsAt): static
    {
        $this->promoEndsAt = $promoEndsAt;

        return $this;
    }

    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCurrentPeriodStart(): ?DateTimeImmutable
    {
        return $this->currentPeriodStart;
    }

    public function setCurrentPeriodStart(?DateTimeImmutable $currentPeriodStart): static
    {
        $this->currentPeriodStart = $currentPeriodStart;

        return $this;
    }

    public function getCurrentPeriodEnd(): ?DateTimeImmutable
    {
        return $this->currentPeriodEnd;
    }

    public function setCurrentPeriodEnd(?DateTimeImmutable $currentPeriodEnd): static
    {
        $this->currentPeriodEnd = $currentPeriodEnd;

        return $this;
    }

    public function getCancelAt(): ?DateTimeImmutable
    {
        return $this->cancelAt;
    }

    public function setCancelAt(?DateTimeImmutable $cancelAt): static
    {
        $this->cancelAt = $cancelAt;

        return $this;
    }

    public function getCanceledAt(): ?DateTimeImmutable
    {
        return $this->canceledAt;
    }

    public function setCanceledAt(?DateTimeImmutable $canceledAt): static
    {
        $this->canceledAt = $canceledAt;

        return $this;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function setCancellationReason(?string $cancellationReason): static
    {
        $this->cancellationReason = $cancellationReason;

        return $this;
    }

    public function getCancellationComment(): ?string
    {
        return $this->cancellationComment;
    }

    public function setCancellationComment(?string $cancellationComment): static
    {
        $this->cancellationComment = $cancellationComment;

        return $this;
    }

    public function getPausedUntil(): ?DateTimeImmutable
    {
        return $this->pausedUntil;
    }

    public function setPausedUntil(?DateTimeImmutable $pausedUntil): static
    {
        $this->pausedUntil = $pausedUntil;

        return $this;
    }

    public function getGraceUntil(): ?DateTimeImmutable
    {
        return $this->graceUntil;
    }

    public function setGraceUntil(?DateTimeImmutable $graceUntil): static
    {
        $this->graceUntil = $graceUntil;

        return $this;
    }
}