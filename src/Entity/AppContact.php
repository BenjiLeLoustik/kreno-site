<?php

namespace App\Entity;

use App\Entity\User\User;
use App\Repository\AppContactRepository;
use App\Support\Enum\AppContactStatus;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: AppContactRepository::class)]
#[ORM\Index(columns: ['token'], unique: true)]
#[ORM\Index(columns: ['status', 'lastMessageAt'])]
#[ORM\Index(columns: ['email'])]
class AppContact extends AbstractBaseEntity
{
    #[ORM\Column(length: 20)]
    private ?string $topic = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\ManyToOne(User::class, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\ManyToOne(Booking::class, onDelete: 'SET NULL')]
    private ?Booking $booking = null;

    #[ORM\Column(length: 12, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column]
    private ?AppContactStatus $status = null;

    #[ORM\Column(length: 64)]
    private ?string $token = null;

    #[ORM\Column]
    private ?DateTimeImmutable $lastMessageAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $closedAt = null;

    #[ORM\OneToMany(AppContactMessage::class, mappedBy: 'contact', cascade: ['persist', 'remove'], orphanRemoval: true, orderBy: ['createdAt' => 'ASC'])]
    private CollectionInterface $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getTopic(): ?string
    {
        return $this->topic;
    }

    public function setTopic(string $topic): static
    {
        $this->topic = $topic;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getStatus(): ?AppContactStatus
    {
        return $this->status;
    }

    public function setStatus(AppContactStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getLastMessageAt(): ?DateTimeImmutable
    {
        return $this->lastMessageAt;
    }

    public function setLastMessageAt(DateTimeImmutable $lastMessageAt): static
    {
        $this->lastMessageAt = $lastMessageAt;

        return $this;
    }

    public function getClosedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?DateTimeImmutable $closedAt): static
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    public function getMessages(): CollectionInterface
    {
        return $this->messages;
    }

    public function addMessage(AppContactMessage $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setContact($this);
        }

        return $this;
    }

    public function removeMessage(AppContactMessage $message): static
    {
        if ($this->messages->removeElement($message) && $message->getContact() === $this) {
            $message->setContact(null);
        }

        return $this;
    }
}