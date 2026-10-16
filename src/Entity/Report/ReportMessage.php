<?php

declare(strict_types=1);

namespace App\Entity\Report;

use App\Entity\AbstractBaseEntity;
use App\Entity\User\User;
use App\Repository\Report\ReportMessageRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: ReportMessageRepository::class)]
class ReportMessage extends AbstractBaseEntity
{
    #[ORM\ManyToOne(Report::class, inversedBy: 'messages', nullable: false, onDelete: 'CASCADE')]
    private ?Report $report = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $author = null;

    #[ORM\Column(type: 'text')]
    private ?string $body = null;

    #[ORM\Column]
    private bool $internal = false;

    public function getReport(): ?Report
    {
        return $this->report;
    }

    public function setReport(?Report $report): static
    {
        $this->report = $report;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function isInternal(): bool
    {
        return $this->internal;
    }

    public function setInternal(bool $internal): static
    {
        $this->internal = $internal;

        return $this;
    }
}