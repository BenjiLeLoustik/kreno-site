<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\AbstractBaseEntity;
use App\Entity\Booking;
use App\Entity\Company\CompanyMember;
use App\Entity\Establishment\EstablishmentFollower;
use App\Repository\User\UserRepository;
use App\User\Enum\UserRole;
use App\User\Enum\UserStatus;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;
use NeoPHP\Package\Security\Contract\PasswordAuthenticatedUserInterface;
use NeoPHP\Package\Security\Contract\UserInterface;

#[ORM\Entity(table: 'users', repository: UserRepository::class)]
#[ORM\Index(columns: ['status'])]
class User extends AbstractBaseEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 80)]
    private ?string $firstName = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column]
    private UserStatus $status = UserStatus::ACTIVE;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $statusReason = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $suspendedUntil = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column]
    private bool $emailNotifications = true;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    #[ORM\OneToMany(CompanyMember::class, mappedBy: 'user')]
    private CollectionInterface $memberships;

    #[ORM\OneToMany(Booking::class, mappedBy: 'customer', orderBy: ['createdAt' => 'DESC'])]
    private CollectionInterface $bookings;

    #[ORM\OneToMany(UserAlert::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $alerts;

    #[ORM\OneToMany(UserNotification::class, mappedBy: 'user', cascade: ['remove'], orphanRemoval: true, orderBy: ['createdAt' => 'DESC'])]
    private CollectionInterface $notifications;

    #[ORM\OneToMany(EstablishmentFollower::class, mappedBy: 'user', cascade: ['remove'], orphanRemoval: true)]
    private CollectionInterface $follows;

    #[ORM\OneToMany(UserToken::class, mappedBy: 'user', cascade: ['remove'], orphanRemoval: true)]
    private CollectionInterface $tokens;

    public function __construct()
    {
        $this->memberships = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->alerts = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->follows = new ArrayCollection();
        $this->tokens = new ArrayCollection();
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getStoredRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function setStatus(UserStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusReason(): ?string
    {
        return $this->statusReason;
    }

    public function setStatusReason(?string $statusReason): static
    {
        $this->statusReason = $statusReason;

        return $this;
    }

    public function getSuspendedUntil(): ?DateTimeImmutable
    {
        return $this->suspendedUntil;
    }

    public function setSuspendedUntil(?DateTimeImmutable $suspendedUntil): static
    {
        $this->suspendedUntil = $suspendedUntil;

        return $this;
    }

    public function getEmailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?DateTimeImmutable $emailVerifiedAt): static
    {
        $this->emailVerifiedAt = $emailVerifiedAt;

        return $this;
    }

    public function isEmailNotifications(): bool
    {
        return $this->emailNotifications;
    }

    public function setEmailNotifications(bool $emailNotifications): static
    {
        $this->emailNotifications = $emailNotifications;

        return $this;
    }

    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getMemberships(): CollectionInterface
    {
        return $this->memberships;
    }

    public function addMembership(CompanyMember $membership): static
    {
        if (!$this->memberships->contains($membership)) {
            $this->memberships->add($membership);
            $membership->setUser($this);
        }

        return $this;
    }

    public function removeMembership(CompanyMember $membership): static
    {
        $this->memberships->removeElement($membership);

        return $this;
    }

    public function getBookings(): CollectionInterface
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setCustomer($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        $this->bookings->removeElement($booking);

        return $this;
    }

    public function getAlerts(): CollectionInterface
    {
        return $this->alerts;
    }

    public function addAlert(UserAlert $alert): static
    {
        if (!$this->alerts->contains($alert)) {
            $this->alerts->add($alert);
            $alert->setUser($this);
        }

        return $this;
    }

    public function removeAlert(UserAlert $alert): static
    {
        if ($this->alerts->removeElement($alert) && $alert->getUser() === $this) {
            $alert->setUser(null);
        }

        return $this;
    }

    public function getNotifications(): CollectionInterface
    {
        return $this->notifications;
    }

    public function addNotification(UserNotification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(UserNotification $notification): static
    {
        if ($this->notifications->removeElement($notification) && $notification->getUser() === $this) {
            $notification->setUser(null);
        }

        return $this;
    }

    public function getFollows(): CollectionInterface
    {
        return $this->follows;
    }

    public function addFollow(EstablishmentFollower $follow): static
    {
        if (!$this->follows->contains($follow)) {
            $this->follows->add($follow);
            $follow->setUser($this);
        }

        return $this;
    }

    public function removeFollow(EstablishmentFollower $follow): static
    {
        if ($this->follows->removeElement($follow) && $follow->getUser() === $this) {
            $follow->setUser(null);
        }

        return $this;
    }

    public function getTokens(): CollectionInterface
    {
        return $this->tokens;
    }

    public function addToken(UserToken $token): static
    {
        if (!$this->tokens->contains($token)) {
            $this->tokens->add($token);
            $token->setUser($this);
        }

        return $this;
    }

    public function removeToken(UserToken $token): static
    {
        if ($this->tokens->removeElement($token) && $token->getUser() === $this) {
            $token->setUser(null);
        }

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, UserRole::USER->value]));
    }
}