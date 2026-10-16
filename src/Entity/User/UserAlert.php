<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\AbstractBaseEntity;
use App\Entity\Category;
use App\Entity\City;
use App\Repository\User\UserAlertRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: UserAlertRepository::class)]
#[ORM\Index(columns: ['enabled', 'city'])]
class UserAlert extends AbstractBaseEntity
{
    #[ORM\ManyToOne(User::class, inversedBy: 'alerts', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(City::class, onDelete: 'SET NULL')]
    private ?City $city = null;

    #[ORM\Column(length: 120)]
    private ?string $label = null;

    #[ORM\Column(type: 'decimal', nullable: true, precision: 10, scale: 7)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', nullable: true, precision: 10, scale: 7)]
    private ?string $longitude = null;

    #[ORM\Column(type: 'smallint')]
    private int $radiusKm = 3;

    #[ORM\Column(type: 'time_immutable', nullable: true)]
    private ?DateTimeImmutable $startTime = null;

    #[ORM\Column(type: 'time_immutable', nullable: true)]
    private ?DateTimeImmutable $endTime = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxPrice = null;

    #[ORM\Column]
    private bool $enabled = true;

    #[ORM\ManyToMany(Category::class, joinTable: 'user_alert_category')]
    private CollectionInterface $categories;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): static
    {
        $this->city = $city;

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

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getRadiusKm(): int
    {
        return $this->radiusKm;
    }

    public function setRadiusKm(int $radiusKm): static
    {
        $this->radiusKm = $radiusKm;

        return $this;
    }

    public function getStartTime(): ?DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(?DateTimeImmutable $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(?DateTimeImmutable $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getMaxPrice(): ?int
    {
        return $this->maxPrice;
    }

    public function setMaxPrice(?int $maxPrice): static
    {
        $this->maxPrice = $maxPrice;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getCategories(): CollectionInterface
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }
}