<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Trait\PositionTrait;
use App\Entity\Trait\SlugTrait;
use App\Enum\City\CityStatus;
use App\Repository\CityRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CityRepository::class)]
#[ORM\Index(columns: ['slug'], unique: true)]
#[ORM\Index(columns: ['status', 'position'])]
class City extends AbstractBaseEntity
{
    use SlugTrait;
    use PositionTrait;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $departmentCode = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(type: 'smallint')]
    private int $radiusKm = 15;

    #[ORM\Column]
    private CityStatus $status = CityStatus::CLOSED;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $openedAt = null;

    #[ORM\OneToMany(CompanyEstablishment::class, mappedBy: 'city')]
    private CollectionInterface $establishments;

    public function __construct()
    {
        $this->establishments = new ArrayCollection();
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

    public function getDepartmentCode(): ?string
    {
        return $this->departmentCode;
    }

    public function setDepartmentCode(?string $departmentCode): static
    {
        $this->departmentCode = $departmentCode;

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

    public function getStatus(): CityStatus
    {
        return $this->status;
    }

    public function setStatus(CityStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getOpenedAt(): ?DateTimeImmutable
    {
        return $this->openedAt;
    }

    public function setOpenedAt(?DateTimeImmutable $openedAt): static
    {
        $this->openedAt = $openedAt;

        return $this;
    }

    public function getEstablishments(): CollectionInterface
    {
        return $this->establishments;
    }

    public function addEstablishment(CompanyEstablishment $establishment): static
    {
        if (!$this->establishments->contains($establishment)) {
            $this->establishments->add($establishment);
            $establishment->setCity($this);
        }

        return $this;
    }

    public function removeEstablishment(CompanyEstablishment $establishment): static
    {
        $this->establishments->removeElement($establishment);

        return $this;
    }
}