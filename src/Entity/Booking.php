<?php

declare(strict_types=1);

namespace App\Entity;

use App\Booking\Enum\BookingStatus;
use App\Entity\Company\CompanyInvoiceLine;
use App\Entity\Establishment\EstablishmentSlot;
use App\Entity\User\User;
use App\Repository\BookingRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: BookingRepository::class)]
#[ORM\Index(columns: ['customer', 'status'])]
#[ORM\Index(columns: ['slot', 'status'])]
#[ORM\Index(columns: ['status', 'noShowAt'])]
class Booking extends AbstractBaseEntity
{
    #[ORM\ManyToOne(EstablishmentSlot::class, inversedBy: 'bookings', nullable: false)]
    private ?EstablishmentSlot $slot = null;

    #[ORM\ManyToOne(User::class, inversedBy: 'bookings', nullable: false)]
    private ?User $customer = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $cancelledBy = null;

    #[ORM\ManyToOne(CompanyInvoiceLine::class, inversedBy: 'bookings', onDelete: 'SET NULL')]
    private ?CompanyInvoiceLine $invoiceLine = null;

    #[ORM\Column(length: 12, unique: true)]
    private ?string $reference = null;

    #[ORM\Column]
    private BookingStatus $status = BookingStatus::CONFIRMED;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    #[ORM\Column]
    private int $price = 0;

    #[ORM\Column]
    private int $fee = 0;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cancellationReason = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $honoredAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $noShowAt = null;

    public function getSlot(): ?EstablishmentSlot
    {
        return $this->slot;
    }

    public function setSlot(?EstablishmentSlot $slot): static
    {
        $this->slot = $slot;

        return $this;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCancelledBy(): ?User
    {
        return $this->cancelledBy;
    }

    public function setCancelledBy(?User $cancelledBy): static
    {
        $this->cancelledBy = $cancelledBy;

        return $this;
    }

    public function getInvoiceLine(): ?CompanyInvoiceLine
    {
        return $this->invoiceLine;
    }

    public function setInvoiceLine(?CompanyInvoiceLine $invoiceLine): static
    {
        $this->invoiceLine = $invoiceLine;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getStatus(): BookingStatus
    {
        return $this->status;
    }

    public function setStatus(BookingStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

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

    public function getFee(): int
    {
        return $this->fee;
    }

    public function setFee(int $fee): static
    {
        $this->fee = $fee;

        return $this;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?DateTimeImmutable $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;

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

    public function getHonoredAt(): ?DateTimeImmutable
    {
        return $this->honoredAt;
    }

    public function setHonoredAt(?DateTimeImmutable $honoredAt): static
    {
        $this->honoredAt = $honoredAt;

        return $this;
    }

    public function getNoShowAt(): ?DateTimeImmutable
    {
        return $this->noShowAt;
    }

    public function setNoShowAt(?DateTimeImmutable $noShowAt): static
    {
        $this->noShowAt = $noShowAt;

        return $this;
    }
}