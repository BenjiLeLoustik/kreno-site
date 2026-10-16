<?php

declare(strict_types=1);

namespace App\Entity\Establishment;

use App\Entity\AbstractBaseEntity;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Trait\PositionTrait;
use App\Repository\Establishment\EstablishmentImageRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: EstablishmentImageRepository::class)]
#[ORM\Index(columns: ['establishment', 'position'])]
class EstablishmentImage extends AbstractBaseEntity
{
    use PositionTrait;

    #[ORM\ManyToOne(CompanyEstablishment::class, inversedBy: 'images', nullable: false, onDelete: 'CASCADE')]
    private ?CompanyEstablishment $establishment = null;

    #[ORM\Column(length: 255)]
    private ?string $path = null;

    #[ORM\Column]
    private int $width = 0;

    #[ORM\Column]
    private int $height = 0;

    #[ORM\Column(length: 50)]
    private ?string $mimeType = null;

    #[ORM\Column]
    private int $size = 0;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $alt = null;

    public function getEstablishment(): ?CompanyEstablishment
    {
        return $this->establishment;
    }

    public function setEstablishment(?CompanyEstablishment $establishment): static
    {
        $this->establishment = $establishment;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(?string $alt): static
    {
        $this->alt = $alt;

        return $this;
    }
}