<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\AbstractBaseEntity;
use App\Repository\User\UserTokenRepository;
use App\User\Enum\UserTokenType;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: UserTokenRepository::class)]
#[ORM\Index(columns: ['user', 'type'])]
class UserToken extends AbstractBaseEntity
{
    #[ORM\ManyToOne(User::class, inversedBy: 'tokens', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column]
    private ?UserTokenType $type = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $tokenHash = null;

    #[ORM\Column]
    private ?DateTimeImmutable $expiresAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $usedAt = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getType(): ?UserTokenType
    {
        return $this->type;
    }

    public function setType(UserTokenType $type): static
    {
        $this->type = $type;

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

    public function getUsedAt(): ?DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function setUsedAt(?DateTimeImmutable $usedAt): static
    {
        $this->usedAt = $usedAt;

        return $this;
    }
}