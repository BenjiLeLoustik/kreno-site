<?php

declare(strict_types=1);

namespace App\Entity\Company;

use App\Entity\AbstractBaseEntity;
use App\Entity\Trait\AddressTrait;
use App\Repository\Company\CompanyConfigRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CompanyConfigRepository::class)]
class CompanyConfig extends AbstractBaseEntity
{
    use AddressTrait;

    #[ORM\OneToOne(Company::class, inversedBy: 'config', nullable: false, onDelete: 'CASCADE')]
    private ?Company $company = null;

    #[ORM\Column(type: 'smallint')]
    private int $defaultDiscount = 30;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $billingName = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $billingEmail = null;

    #[ORM\Column]
    private bool $bookingEmailNotifications = true;

    #[ORM\Column]
    private bool $monthlyTips = false;

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        if ($company !== null && $company->getConfig() !== $this) {
            $company->setConfig($this);
        }

        return $this;
    }

    public function getDefaultDiscount(): int
    {
        return $this->defaultDiscount;
    }

    public function setDefaultDiscount(int $defaultDiscount): static
    {
        $this->defaultDiscount = $defaultDiscount;

        return $this;
    }

    public function getBillingName(): ?string
    {
        return $this->billingName;
    }

    public function setBillingName(?string $billingName): static
    {
        $this->billingName = $billingName;

        return $this;
    }

    public function getBillingEmail(): ?string
    {
        return $this->billingEmail;
    }

    public function setBillingEmail(?string $billingEmail): static
    {
        $this->billingEmail = $billingEmail;

        return $this;
    }

    public function isBookingEmailNotifications(): bool
    {
        return $this->bookingEmailNotifications;
    }

    public function setBookingEmailNotifications(bool $bookingEmailNotifications): static
    {
        $this->bookingEmailNotifications = $bookingEmailNotifications;

        return $this;
    }

    public function isMonthlyTips(): bool
    {
        return $this->monthlyTips;
    }

    public function setMonthlyTips(bool $monthlyTips): static
    {
        $this->monthlyTips = $monthlyTips;

        return $this;
    }
}