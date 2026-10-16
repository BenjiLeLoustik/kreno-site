<?php

declare(strict_types=1);

namespace App\Entity\Establishment;

use App\Entity\AbstractBaseEntity;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use App\Repository\Establishment\EstablishmentFollowerRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: EstablishmentFollowerRepository::class)]
#[ORM\Index(columns: ['establishment', 'user'], unique: true)]
class EstablishmentFollower extends AbstractBaseEntity
{
    #[ORM\ManyToOne(CompanyEstablishment::class, inversedBy: 'followers', nullable: false, onDelete: 'CASCADE')]
    private ?CompanyEstablishment $establishment = null;

    #[ORM\ManyToOne(User::class, inversedBy: 'follows', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    public function getEstablishment(): ?CompanyEstablishment
    {
        return $this->establishment;
    }

    public function setEstablishment(?CompanyEstablishment $establishment): static
    {
        $this->establishment = $establishment;

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
}