<?php

declare(strict_types=1);

namespace App\Entity\Report;

use App\Entity\AbstractBaseEntity;
use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use App\Report\Enum\ReportDirection;
use App\Report\Enum\ReportReason;
use App\Report\Enum\ReportStatus;
use App\Repository\Report\ReportRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: ReportRepository::class)]
#[ORM\Index(columns: ['status', 'createdAt'])]
#[ORM\Index(columns: ['establishment', 'status'])]
#[ORM\Index(columns: ['reporter'])]
class Report extends AbstractBaseEntity
{
    #[ORM\ManyToOne(User::class, nullable: false)]
    private ?User $reporter = null;

    #[ORM\ManyToOne(CompanyEstablishment::class, onDelete: 'SET NULL')]
    private ?CompanyEstablishment $establishment = null;

    #[ORM\ManyToOne(Booking::class, onDelete: 'SET NULL')]
    private ?Booking $booking = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $reportedUser = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $handledBy = null;

    #[ORM\Column]
    private ?ReportDirection $direction = null;

    #[ORM\Column]
    private ?ReportReason $reason = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $attachmentPath = null;

    #[ORM\Column]
    private ReportStatus $status = ReportStatus::SENT;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $resolution = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $handledAt = null;

    #[ORM\OneToMany(ReportMessage::class, mappedBy: 'report', cascade: ['persist', 'remove'], orphanRemoval: true, orderBy: ['createdAt' => 'ASC'])]
    private CollectionInterface $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getReporter(): ?User
    {
        return $this->reporter;
    }

    public function setReporter(?User $reporter): static
    {
        $this->reporter = $reporter;

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

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): static
    {
        $this->booking = $booking;

        return $this;
    }

    public function getReportedUser(): ?User
    {
        return $this->reportedUser;
    }

    public function setReportedUser(?User $reportedUser): static
    {
        $this->reportedUser = $reportedUser;

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

    public function getDirection(): ?ReportDirection
    {
        return $this->direction;
    }

    public function setDirection(ReportDirection $direction): static
    {
        $this->direction = $direction;

        return $this;
    }

    public function getReason(): ?ReportReason
    {
        return $this->reason;
    }

    public function setReason(ReportReason $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAttachmentPath(): ?string
    {
        return $this->attachmentPath;
    }

    public function setAttachmentPath(?string $attachmentPath): static
    {
        $this->attachmentPath = $attachmentPath;

        return $this;
    }

    public function getStatus(): ReportStatus
    {
        return $this->status;
    }

    public function setStatus(ReportStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function setResolution(?string $resolution): static
    {
        $this->resolution = $resolution;

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

    public function getMessages(): CollectionInterface
    {
        return $this->messages;
    }

    public function addMessage(ReportMessage $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setReport($this);
        }

        return $this;
    }

    public function removeMessage(ReportMessage $message): static
    {
        if ($this->messages->removeElement($message) && $message->getReport() === $this) {
            $message->setReport(null);
        }

        return $this;
    }
}