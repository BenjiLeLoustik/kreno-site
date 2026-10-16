<?php

declare(strict_types=1);

namespace App\Entity\Company;

use App\Company\Enum\CompanyRole;
use App\Entity\AbstractBaseEntity;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentRole;
use App\Repository\Company\CompanyInvitationRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CompanyInvitationRepository::class)]
#[ORM\Index(columns: ['company', 'email'])]
class CompanyInvitation extends AbstractBaseEntity
{
    #[ORM\ManyToOne(Company::class, inversedBy: 'invitations', nullable: false, onDelete: 'CASCADE')]
    private ?Company $company = null;

    #[ORM\ManyToOne(CompanyEstablishment::class, onDelete: 'CASCADE')]
    private ?CompanyEstablishment $establishment = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $invitedBy = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(nullable: true)]
    private ?CompanyRole $role = null;

    #[ORM\Column(nullable: true)]
    private ?EstablishmentRole $establishmentRole = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $tokenHash = null;

    #[ORM\Column]
    private ?DateTimeImmutable $expiresAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $acceptedAt = null;

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

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

    public function getInvitedBy(): ?User
    {
        return $this->invitedBy;
    }

    public function setInvitedBy(?User $invitedBy): static
    {
        $this->invitedBy = $invitedBy;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

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

    public function getEstablishmentRole(): ?EstablishmentRole
    {
        return $this->establishmentRole;
    }

    public function setEstablishmentRole(?EstablishmentRole $establishmentRole): static
    {
        $this->establishmentRole = $establishmentRole;

        return $this;
    }

    public function getTokenHash(): ?string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(string $tokenHash): static
    {
        $this->tokenHash = $tokenHash;

        return $this;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getAcceptedAt(): ?DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(?DateTimeImmutable $acceptedAt): static
    {
        $this->acceptedAt = $acceptedAt;

        return $this;
    }
}