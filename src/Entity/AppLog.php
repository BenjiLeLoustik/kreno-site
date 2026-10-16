<?php

namespace App\Entity;

use App\Entity\User\User;
use App\Log\Enum\AppLogLevel;
use App\Log\Enum\AppLogStatus;
use App\Repository\AppLogRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: AppLogRepository::class)]
#[ORM\Index(columns: ['fingerprint'], unique: true)]
#[ORM\Index(columns: ['status', 'lastSeenAt'])]
class AppLog extends AbstractBaseEntity
{
    #[ORM\Column(length: 40)]
    private ?string $fingerprint = null;

    #[ORM\Column]
    private ?AppLogLevel $level = null;

    #[ORM\Column]
    private ?AppLogStatus $status = null;

    #[ORM\Column(length: 255)]
    private ?string $exceptionClass = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $file = null;

    #[ORM\Column(nullable: true)]
    private ?int $line = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $trace = null;

    #[ORM\Column]
    private int $statusCode = 500;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $method = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $url = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column]
    private int $occurrences = 1;

    #[ORM\Column]
    private ?DateTimeImmutable $firstSeenAt = null;

    #[ORM\Column]
    private ?DateTimeImmutable $lastSeenAt = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $handledBy = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $handledAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(string $fingerprint): static
    {
        $this->fingerprint = $fingerprint;

        return $this;
    }

    public function getLevel(): ?AppLogLevel
    {
        return $this->level;
    }

    public function setLevel(AppLogLevel $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getStatus(): ?AppLogStatus
    {
        return $this->status;
    }

    public function setStatus(AppLogStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getExceptionClass(): ?string
    {
        return $this->exceptionClass;
    }

    public function setExceptionClass(string $exceptionClass): static
    {
        $this->exceptionClass = $exceptionClass;

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

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(?string $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }

    public function setLine(?int $line): static
    {
        $this->line = $line;

        return $this;
    }

    public function getTrace(): ?string
    {
        return $this->trace;
    }

    public function setTrace(?string $trace): static
    {
        $this->trace = $trace;

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): static
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

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

    public function getOccurrences(): int
    {
        return $this->occurrences;
    }

    public function setOccurrences(int $occurrences): static
    {
        $this->occurrences = $occurrences;

        return $this;
    }

    public function getFirstSeenAt(): ?DateTimeImmutable
    {
        return $this->firstSeenAt;
    }

    public function setFirstSeenAt(DateTimeImmutable $firstSeenAt): static
    {
        $this->firstSeenAt = $firstSeenAt;

        return $this;
    }

    public function getLastSeenAt(): ?DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function setLastSeenAt(DateTimeImmutable $lastSeenAt): static
    {
        $this->lastSeenAt = $lastSeenAt;

        return $this;
    }

    public function getHandledBy(): ?User
    {
        return $this->handledBy;
    }

    public function setHandledBy(?User $handledBy): static
    {
        $this->handledBy = $handledBy;

        return $this;
    }

    public function getHandledAt(): ?DateTimeImmutable
    {
        return $this->handledAt;
    }

    public function setHandledAt(?DateTimeImmutable $handledAt): static
    {
        $this->handledAt = $handledAt;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }
}