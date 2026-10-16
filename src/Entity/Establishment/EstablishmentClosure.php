<?php

declare(strict_types=1);

namespace App\Entity\Establishment;

use App\Entity\AbstractBaseEntity;
use App\Entity\Company\CompanyEstablishment;
use App\Repository\Establishment\EstablishmentClosureRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: EstablishmentClosureRepository::class)]
#[ORM\Index(columns: ['establishment', 'startsOn', 'endsOn'])]
class EstablishmentClosure extends AbstractBaseEntity
{
    #[ORM\ManyToOne(CompanyEstablishment::class, inversedBy: 'closures', nullable: false, onDelete: 'CASCADE')]
    private ?CompanyEstablishment $establishment = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?DateTimeImmutable $startsOn = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?DateTimeImmutable $endsOn = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $reason = null;

    public function getEstablishment(): ?CompanyEstablishment
    {
        return $this->establishment;
    }

    public function setEstablishment(?CompanyEstablishment $establishment): static
    {
        $this->establishment = $establishment;

        return $this;
    }

    public function getStartsOn(): ?DateTimeImmutable
    {
        return $this->startsOn;
    }

    public function setStartsOn(DateTimeImmutable $startsOn): static
    {
        $this->startsOn = $startsOn;

        return $this;
    }

    public function getEndsOn(): ?DateTimeImmutable
    {
        return $this->endsOn;
    }

    public function setEndsOn(DateTimeImmutable $endsOn): static
    {
        $this->endsOn = $endsOn;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }
}