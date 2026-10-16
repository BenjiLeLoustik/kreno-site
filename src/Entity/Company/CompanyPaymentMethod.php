<?php

declare(strict_types=1);

namespace App\Entity\Company;

use App\Billing\Enum\PaymentMethodType;
use App\Entity\AbstractBaseEntity;
use App\Entity\User\User;
use App\Repository\Company\CompanyPaymentMethodRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CompanyPaymentMethodRepository::class)]
#[ORM\Index(columns: ['company', 'defaultMethod'])]
class CompanyPaymentMethod extends AbstractBaseEntity
{
    #[ORM\ManyToOne(Company::class, inversedBy: 'paymentMethods', nullable: false, onDelete: 'CASCADE')]
    private ?Company $company = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $addedBy = null;

    #[ORM\Column]
    private ?PaymentMethodType $type = null;

    #[ORM\Column(length: 120, unique: true)]
    private ?string $providerReference = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $brand = null;

    #[ORM\Column(length: 4)]
    private ?string $last4 = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $expMonth = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $expYear = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $holderName = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $mandateReference = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $mandateAcceptedAt = null;

    #[ORM\Column]
    private bool $defaultMethod = false;

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getAddedBy(): ?User
    {
        return $this->addedBy;
    }

    public function setAddedBy(?User $addedBy): static
    {
        $this->addedBy = $addedBy;

        return $this;
    }

    public function getType(): ?PaymentMethodType
    {
        return $this->type;
    }

    public function setType(PaymentMethodType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getProviderReference(): ?string
    {
        return $this->providerReference;
    }

    public function setProviderReference(string $providerReference): static
    {
        $this->providerReference = $providerReference;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getLast4(): ?string
    {
        return $this->last4;
    }

    public function setLast4(string $last4): static
    {
        $this->last4 = $last4;

        return $this;
    }

    public function getExpMonth(): ?int
    {
        return $this->expMonth;
    }

    public function setExpMonth(?int $expMonth): static
    {
        $this->expMonth = $expMonth;

        return $this;
    }

    public function getExpYear(): ?int
    {
        return $this->expYear;
    }

    public function setExpYear(?int $expYear): static
    {
        $this->expYear = $expYear;

        return $this;
    }

    public function getHolderName(): ?string
    {
        return $this->holderName;
    }

    public function setHolderName(?string $holderName): static
    {
        $this->holderName = $holderName;

        return $this;
    }

    public function getMandateReference(): ?string
    {
        return $this->mandateReference;
    }

    public function setMandateReference(?string $mandateReference): static
    {
        $this->mandateReference = $mandateReference;

        return $this;
    }

    public function getMandateAcceptedAt(): ?DateTimeImmutable
    {
        return $this->mandateAcceptedAt;
    }

    public function setMandateAcceptedAt(?DateTimeImmutable $mandateAcceptedAt): static
    {
        $this->mandateAcceptedAt = $mandateAcceptedAt;

        return $this;
    }

    public function isDefaultMethod(): bool
    {
        return $this->defaultMethod;
    }

    public function setDefaultMethod(bool $defaultMethod): static
    {
        $this->defaultMethod = $defaultMethod;

        return $this;
    }
}