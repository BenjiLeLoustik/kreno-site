<?php

declare(strict_types=1);

namespace App\Entity\Company;

use App\Billing\Enum\PaymentStatus;
use App\Entity\AbstractBaseEntity;
use App\Entity\User\User;
use App\Repository\Company\CompanyPaymentRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CompanyPaymentRepository::class)]
#[ORM\Index(columns: ['status', 'nextRetryAt'])]
class CompanyPayment extends AbstractBaseEntity
{
    #[ORM\ManyToOne(CompanyInvoice::class, inversedBy: 'payments', nullable: false, onDelete: 'CASCADE')]
    private ?CompanyInvoice $invoice = null;

    #[ORM\ManyToOne(CompanyPaymentMethod::class, onDelete: 'SET NULL')]
    private ?CompanyPaymentMethod $paymentMethod = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $initiatedBy = null;

    #[ORM\Column]
    private int $amount = 0;

    #[ORM\Column]
    private PaymentStatus $status = PaymentStatus::PENDING;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $providerReference = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $failureCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $failureMessage = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $attemptedAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $succeededAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $nextRetryAt = null;

    public function getInvoice(): ?CompanyInvoice
    {
        return $this->invoice;
    }

    public function setInvoice(?CompanyInvoice $invoice): static
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getPaymentMethod(): ?CompanyPaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?CompanyPaymentMethod $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getInitiatedBy(): ?User
    {
        return $this->initiatedBy;
    }

    public function setInitiatedBy(?User $initiatedBy): static
    {
        $this->initiatedBy = $initiatedBy;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function setStatus(PaymentStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getProviderReference(): ?string
    {
        return $this->providerReference;
    }

    public function setProviderReference(?string $providerReference): static
    {
        $this->providerReference = $providerReference;

        return $this;
    }

    public function getFailureCode(): ?string
    {
        return $this->failureCode;
    }

    public function setFailureCode(?string $failureCode): static
    {
        $this->failureCode = $failureCode;

        return $this;
    }

    public function getFailureMessage(): ?string
    {
        return $this->failureMessage;
    }

    public function setFailureMessage(?string $failureMessage): static
    {
        $this->failureMessage = $failureMessage;

        return $this;
    }

    public function getAttemptedAt(): ?DateTimeImmutable
    {
        return $this->attemptedAt;
    }

    public function setAttemptedAt(?DateTimeImmutable $attemptedAt): static
    {
        $this->attemptedAt = $attemptedAt;

        return $this;
    }

    public function getSucceededAt(): ?DateTimeImmutable
    {
        return $this->succeededAt;
    }

    public function setSucceededAt(?DateTimeImmutable $succeededAt): static
    {
        $this->succeededAt = $succeededAt;

        return $this;
    }

    public function getNextRetryAt(): ?DateTimeImmutable
    {
        return $this->nextRetryAt;
    }

    public function setNextRetryAt(?DateTimeImmutable $nextRetryAt): static
    {
        $this->nextRetryAt = $nextRetryAt;

        return $this;
    }
}