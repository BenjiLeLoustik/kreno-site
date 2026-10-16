<?php

declare(strict_types=1);

namespace App\Entity\Establishment;

use App\Entity\AbstractBaseEntity;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Establishment\Enum\EstablishmentRole;
use App\Repository\Establishment\EstablishmentMemberRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: EstablishmentMemberRepository::class)]
#[ORM\Index(columns: ['establishment', 'member'], unique: true)]
class EstablishmentMember extends AbstractBaseEntity
{
    #[ORM\ManyToOne(CompanyEstablishment::class, inversedBy: 'members', nullable: false, onDelete: 'CASCADE')]
    private ?CompanyEstablishment $establishment = null;

    #[ORM\ManyToOne(CompanyMember::class, inversedBy: 'establishmentRoles', nullable: false, onDelete: 'CASCADE')]
    private ?CompanyMember $member = null;

    #[ORM\Column]
    private EstablishmentRole $role = EstablishmentRole::EMPLOYEE;

    #[ORM\Column]
    private bool $canManageBookings = false;

    public function getEstablishment(): ?CompanyEstablishment
    {
        return $this->establishment;
    }

    public function setEstablishment(?CompanyEstablishment $establishment): static
    {
        $this->establishment = $establishment;

        return $this;
    }

    public function getMember(): ?CompanyMember
    {
        return $this->member;
    }

    public function setMember(?CompanyMember $member): static
    {
        $this->member = $member;

        return $this;
    }

    public function getRole(): EstablishmentRole
    {
        return $this->role;
    }

    public function setRole(EstablishmentRole $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function isCanManageBookings(): bool
    {
        return $this->canManageBookings;
    }

    public function setCanManageBookings(bool $canManageBookings): static
    {
        $this->canManageBookings = $canManageBookings;

        return $this;
    }
}