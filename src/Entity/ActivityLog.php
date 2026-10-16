<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\User\User;
use App\Entity\Website\WebsiteConfig;
use App\Log\Enum\ActivityType;
use App\Repository\ActivityLogRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: ActivityLogRepository::class)]
#[ORM\Index(columns: ['website', 'createdAt'])]
#[ORM\Index(columns: ['type', 'createdAt'])]
class ActivityLog extends AbstractBaseEntity
{
    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $actor = null;

    #[ORM\ManyToOne(WebsiteConfig::class, onDelete: 'SET NULL')]
    private ?WebsiteConfig $website = null;

    #[ORM\Column]
    private ?ActivityType $type = null;

    #[ORM\Column(length: 255)]
    private ?string $message = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $subjectType = null;

    #[ORM\Column(nullable: true)]
    private ?int $subjectId = null;

    #[ORM\Column(type: 'json')]
    private array $changes = [];

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function setActor(?User $actor): static
    {
        $this->actor = $actor;

        return $this;
    }

    public function getWebsite(): ?WebsiteConfig
    {
        return $this->website;
    }

    public function setWebsite(?WebsiteConfig $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getType(): ?ActivityType
    {
        return $this->type;
    }

    public function setType(ActivityType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getSubjectType(): ?string
    {
        return $this->subjectType;
    }

    public function setSubjectType(?string $subjectType): static
    {
        $this->subjectType = $subjectType;

        return $this;
    }

    public function getSubjectId(): ?int
    {
        return $this->subjectId;
    }

    public function setSubjectId(?int $subjectId): static
    {
        $this->subjectId = $subjectId;

        return $this;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function setChanges(array $changes): static
    {
        $this->changes = $changes;

        return $this;
    }
}