<?php

declare(strict_types=1);

namespace App\Entity\Company;

use App\Company\Enum\CompanyRole;
use App\Company\Enum\MemberStatus;
use App\Entity\AbstractBaseEntity;
use App\Entity\Establishment\EstablishmentMember;
use App\Entity\User\User;
use App\Repository\Company\CompanyMemberRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CompanyMemberRepository::class)]
#[ORM\Index(columns: ['company', 'user'], unique: true)]
class CompanyMember extends AbstractBaseEntity
{
    #[ORM\ManyToOne(Company::class, inversedBy: 'members', nullable: false, onDelete: 'CASCADE')]
    private ?Company $company = null;

    #[ORM\ManyToOne(User::class, inversedBy: 'memberships', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $invitedBy = null;

    #[ORM\Column(nullable: true)]
    private ?CompanyRole $role = null;

    #[ORM\Column]
    private MemberStatus $status = MemberStatus::ACTIVE;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $joinedAt = null;

    #[ORM\OneToMany(EstablishmentMember::class, mappedBy: 'member', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $establishmentRoles;

    public function __construct()
    {
        $this->establishmentRoles = new ArrayCollection();
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
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

    public function getInvitedBy(): ?User
    {
        return $this->invitedBy;
    }

    public function setInvitedBy(?User $invitedBy): static
    {
        $this->invitedBy = $invitedBy;

        return $this;
    }

    public function getRole(): ?CompanyRole
    {
        return $this->role;
    }

    public function setRole(?CompanyRole $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getStatus(): MemberStatus
    {
        return $this->status;
    }

    public function setStatus(MemberStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getJoinedAt(): ?DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(?DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    public function getEstablishmentRoles(): CollectionInterface
    {
        return $this->establishmentRoles;
    }

    public function addEstablishmentRole(EstablishmentMember $establishmentRole): static
    {
        if (!$this->establishmentRoles->contains($establishmentRole)) {
            $this->establishmentRoles->add($establishmentRole);
            $establishmentRole->setMember($this);
        }

        return $this;
    }

    public function removeEstablishmentRole(EstablishmentMember $establishmentRole): static
    {
        if ($this->establishmentRoles->removeElement($establishmentRole) && $establishmentRole->getMember() === $this) {
            $establishmentRole->setMember(null);
        }

        return $this;
    }
}