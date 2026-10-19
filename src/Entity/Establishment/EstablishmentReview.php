<?php

namespace App\Entity\Establishment;

use App\Entity\AbstractBaseEntity;
use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use App\Repository\Establishment\EstablishmentReviewRepository;
use App\Review\Enum\ReviewStatus;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: EstablishmentReviewRepository::class)]
#[ORM\Index(columns: ['establishment', 'status', 'createdAt'])]
#[ORM\Index(columns: ['customer', 'createdAt'])]
#[ORM\Index(columns: ['status', 'updatedAt'])]
class EstablishmentReview extends AbstractBaseEntity
{
    #[ORM\ManyToOne(CompanyEstablishment::class, nullable: false, onDelete: 'CASCADE')]
    private ?CompanyEstablishment $establishment = null;

    #[ORM\OneToOne(Booking::class, nullable: false, onDelete: 'CASCADE')]
    private ?Booking $booking = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $customer = null;

    #[ORM\Column]
    private int $rating = 5;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private ReviewStatus $status = ReviewStatus::PUBLISHED;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reply = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $repliedAt = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $repliedBy = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $reportReason = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $reportedAt = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $reportedBy = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $moderationNote = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $moderatedAt = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $moderatedBy = null;

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

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getStatus(): ReviewStatus
    {
        return $this->status;
    }

    public function setStatus(ReviewStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getReply(): ?string
    {
        return $this->reply;
    }

    public function setReply(?string $reply): static
    {
        $this->reply = $reply;

        return $this;
    }

    public function getRepliedAt(): ?DateTimeImmutable
    {
        return $this->repliedAt;
    }

    public function setRepliedAt(?DateTimeImmutable $repliedAt): static
    {
        $this->repliedAt = $repliedAt;

        return $this;
    }

    public function getRepliedBy(): ?User
    {
        return $this->repliedBy;
    }

    public function setRepliedBy(?User $repliedBy): static
    {
        $this->repliedBy = $repliedBy;

        return $this;
    }

    public function getReportReason(): ?string
    {
        return $this->reportReason;
    }

    public function setReportReason(?string $reportReason): static
    {
        $this->reportReason = $reportReason;

        return $this;
    }

    public function getReportedAt(): ?DateTimeImmutable
    {
        return $this->reportedAt;
    }

    public function setReportedAt(?DateTimeImmutable $reportedAt): static
    {
        $this->reportedAt = $reportedAt;

        return $this;
    }

    public function getReportedBy(): ?User
    {
        return $this->reportedBy;
    }

    public function setReportedBy(?User $reportedBy): static
    {
        $this->reportedBy = $reportedBy;

        return $this;
    }

    public function getModerationNote(): ?string
    {
        return $this->moderationNote;
    }

    public function setModerationNote(?string $moderationNote): static
    {
        $this->moderationNote = $moderationNote;

        return $this;
    }

    public function getModeratedAt(): ?DateTimeImmutable
    {
        return $this->moderatedAt;
    }

    public function setModeratedAt(?DateTimeImmutable $moderatedAt): static
    {
        $this->moderatedAt = $moderatedAt;

        return $this;
    }

    public function getModeratedBy(): ?User
    {
        return $this->moderatedBy;
    }

    public function setModeratedBy(?User $moderatedBy): static
    {
        $this->moderatedBy = $moderatedBy;

        return $this;
    }
}