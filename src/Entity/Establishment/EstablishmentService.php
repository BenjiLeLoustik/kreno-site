<?php

declare(strict_types=1);

namespace App\Entity\Establishment;

use App\Entity\AbstractBaseEntity;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Trait\PositionTrait;
use App\Repository\Establishment\EstablishmentServiceRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: EstablishmentServiceRepository::class)]
#[ORM\Index(columns: ['establishment', 'active', 'position'])]
class EstablishmentService extends AbstractBaseEntity
{
    use PositionTrait;

    #[ORM\ManyToOne(CompanyEstablishment::class, inversedBy: 'services', nullable: false, onDelete: 'CASCADE')]
    private ?CompanyEstablishment $establishment = null;

    #[ORM\Column(length: 120)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private int $price = 0;

    #[ORM\Column(type: 'smallint')]
    private int $duration = 30;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $defaultDiscount = null;

    #[ORM\Column]
    private bool $active = true;

    public function getEstablishment(): ?CompanyEstablishment
    {
        return $this->establishment;
    }

    public function setEstablishment(?CompanyEstablishment $establishment): static
    {
        $this->establishment = $establishment;

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

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getDefaultDiscount(): ?int
    {
        return $this->defaultDiscount;
    }

    public function setDefaultDiscount(?int $defaultDiscount): static
    {
        $this->defaultDiscount = $defaultDiscount;

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