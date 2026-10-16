<?php

declare(strict_types=1);

namespace App\Entity\Establishment;

use App\Entity\AbstractBaseEntity;
use App\Entity\Company\CompanyEstablishment;
use App\Repository\Establishment\EstablishmentOpeningHourRepository;
use App\Shared\Enum\Weekday;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: EstablishmentOpeningHourRepository::class)]
#[ORM\Index(columns: ['establishment', 'weekday'])]
#[ORM\Index(columns: ['weekday', 'opensAt', 'closesAt'])]
class EstablishmentOpeningHour extends AbstractBaseEntity
{
    #[ORM\ManyToOne(CompanyEstablishment::class, inversedBy: 'openingHours', nullable: false, onDelete: 'CASCADE')]
    private ?CompanyEstablishment $establishment = null;

    #[ORM\Column(type: 'smallint')]
    private Weekday $weekday = Weekday::MONDAY;

    #[ORM\Column(type: 'time_immutable')]
    private ?DateTimeImmutable $opensAt = null;

    #[ORM\Column(type: 'time_immutable')]
    private ?DateTimeImmutable $closesAt = null;

    public function getEstablishment(): ?CompanyEstablishment
    {
        return $this->establishment;
    }

    public function setEstablishment(?CompanyEstablishment $establishment): static
    {
        $this->establishment = $establishment;

        return $this;
    }

    public function getWeekday(): Weekday
    {
        return $this->weekday;
    }

    public function setWeekday(Weekday $weekday): static
    {
        $this->weekday = $weekday;

        return $this;
    }

    public function getOpensAt(): ?DateTimeImmutable
    {
        return $this->opensAt;
    }

    public function setOpensAt(DateTimeImmutable $opensAt): static
    {
        $this->opensAt = $opensAt;

        return $this;
    }

    public function getClosesAt(): ?DateTimeImmutable
    {
        return $this->closesAt;
    }

    public function setClosesAt(DateTimeImmutable $closesAt): static
    {
        $this->closesAt = $closesAt;

        return $this;
    }
}