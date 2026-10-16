<?php

declare(strict_types=1);

namespace App\Entity\Establishment;

use App\Entity\AbstractBaseEntity;
use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use App\Establishment\Enum\SlotStatus;
use App\Repository\Establishment\EstablishmentSlotRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: EstablishmentSlotRepository::class)]
#[ORM\Index(columns: ['status', 'startsAt'])]
#[ORM\Index(columns: ['establishment', 'startsAt'])]
#[ORM\Index(columns: ['visibleUntil'])]
class EstablishmentSlot extends AbstractBaseEntity
{
    #[ORM\ManyToOne(CompanyEstablishment::class, inversedBy: 'slots', nullable: false, onDelete: 'CASCADE')]
    private ?CompanyEstablishment $establishment = null;

    #[ORM\ManyToOne(EstablishmentService::class, nullable: false)]
    private ?EstablishmentService $service = null;

    #[ORM\ManyToOne(EstablishmentMember::class, onDelete: 'SET NULL')]
    private ?EstablishmentMember $staff = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $publishedBy = null;

    #[ORM\Column]
    private ?DateTimeImmutable $startsAt = null;

    #[ORM\Column]
    private ?DateTimeImmutable $endsAt = null;

    #[ORM\Column]
    private int $originalPrice = 0;

    #[ORM\Column(type: 'smallint')]
    private int $discount = 0;

    #[ORM\Column]
    private int $price = 0;

    #[ORM\Column]
    private ?DateTimeImmutable $visibleUntil = null;

    #[ORM\Column]
    private SlotStatus $status = SlotStatus::AVAILABLE;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $withdrawnAt = null;

    #[ORM\OneToMany(Booking::class, mappedBy: 'slot', cascade: ['persist'], orderBy: ['createdAt' => 'DESC'])]
    private CollectionInterface $bookings;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
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

    public function getService(): ?EstablishmentService
    {
        return $this->service;
    }

    public function setService(?EstablishmentService $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getStaff(): ?EstablishmentMember
    {
        return $this->staff;
    }

    public function setStaff(?EstablishmentMember $staff): static
    {
        $this->staff = $staff;

        return $this;
    }

    public function getPublishedBy(): ?User
    {
        return $this->publishedBy;
    }

    public function setPublishedBy(?User $publishedBy): static
    {
        $this->publishedBy = $publishedBy;

        return $this;
    }

    public function getStartsAt(): ?DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(DateTimeImmutable $startsAt): static
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): ?DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(DateTimeImmutable $endsAt): static
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function getOriginalPrice(): int
    {
        return $this->originalPrice;
    }

    public function setOriginalPrice(int $originalPrice): static
    {
        $this->originalPrice = $originalPrice;

        return $this;
    }

    public function getDiscount(): int
    {
        return $this->discount;
    }

    public function setDiscount(int $discount): static
    {
        $this->discount = $discount;

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

    public function getVisibleUntil(): ?DateTimeImmutable
    {
        return $this->visibleUntil;
    }

    public function setVisibleUntil(DateTimeImmutable $visibleUntil): static
    {
        $this->visibleUntil = $visibleUntil;

        return $this;
    }

    public function getStatus(): SlotStatus
    {
        return $this->status;
    }

    public function setStatus(SlotStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getWithdrawnAt(): ?DateTimeImmutable
    {
        return $this->withdrawnAt;
    }

    public function setWithdrawnAt(?DateTimeImmutable $withdrawnAt): static
    {
        $this->withdrawnAt = $withdrawnAt;

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
            $booking->setSlot($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        $this->bookings->removeElement($booking);

        return $this;
    }
}