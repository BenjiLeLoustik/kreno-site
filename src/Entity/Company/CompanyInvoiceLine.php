<?php

declare(strict_types=1);

namespace App\Entity\Company;

use App\Entity\AbstractBaseEntity;
use App\Entity\Booking;
use App\Entity\Trait\PositionTrait;
use App\Repository\Company\CompanyInvoiceLineRepository;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CompanyInvoiceLineRepository::class)]
class CompanyInvoiceLine extends AbstractBaseEntity
{
    use PositionTrait;

    #[ORM\ManyToOne(CompanyInvoice::class, inversedBy: 'lines', nullable: false, onDelete: 'CASCADE')]
    private ?CompanyInvoice $invoice = null;

    #[ORM\ManyToOne(CompanyEstablishment::class, onDelete: 'SET NULL')]
    private ?CompanyEstablishment $establishment = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column]
    private int $unitPrice = 0;

    #[ORM\Column]
    private int $total = 0;

    #[ORM\OneToMany(Booking::class, mappedBy: 'invoiceLine')]
    private CollectionInterface $bookings;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
    }

    public function getInvoice(): ?CompanyInvoice
    {
        return $this->invoice;
    }

    public function setInvoice(?CompanyInvoice $invoice): static
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getEstablishment(): ?CompanyEstablishment
    {
        return $this->establishment;
    }

    public function setEstablishment(?CompanyEstablishment $establishment): static
    {
        $this->establishment = $establishment;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

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

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getBookings(): CollectionInterface
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setInvoiceLine($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking) && $booking->getInvoiceLine() === $this) {
            $booking->setInvoiceLine(null);
        }

        return $this;
    }
}