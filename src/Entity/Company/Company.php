<?php

declare(strict_types=1);

namespace App\Entity\Company;

use App\Company\Enum\CompanyKind;
use App\Company\Enum\CompanyStatus;
use App\Entity\AbstractBaseEntity;
use App\Entity\Trait\AddressTrait;
use App\Repository\Company\CompanyRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CompanyRepository::class)]
#[ORM\Index(columns: ['status'])]
class Company extends AbstractBaseEntity
{
    use AddressTrait;

    #[ORM\Column(length: 120)]
    private ?string $name = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $legalName = null;

    #[ORM\Column(length: 9, nullable: true, unique: true)]
    private ?string $siren = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $vatNumber = null;

    #[ORM\Column]
    private CompanyKind $kind = CompanyKind::SOLO;

    #[ORM\Column]
    private CompanyStatus $status = CompanyStatus::ONBOARDING;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $verifiedAt = null;

    #[ORM\OneToOne(CompanyConfig::class, mappedBy: 'company', cascade: ['persist', 'remove'])]
    private ?CompanyConfig $config = null;

    #[ORM\OneToMany(CompanyMember::class, mappedBy: 'company', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $members;

    #[ORM\OneToMany(CompanyInvitation::class, mappedBy: 'company', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $invitations;

    #[ORM\OneToMany(CompanyEstablishment::class, mappedBy: 'company', cascade: ['persist', 'remove'], orphanRemoval: true, orderBy: ['name' => 'ASC'])]
    private CollectionInterface $establishments;

    #[ORM\OneToMany(CompanySubscription::class, mappedBy: 'company', cascade: ['persist'], orderBy: ['createdAt' => 'DESC'])]
    private CollectionInterface $subscriptions;

    #[ORM\OneToMany(CompanyPaymentMethod::class, mappedBy: 'company', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $paymentMethods;

    #[ORM\OneToMany(CompanyInvoice::class, mappedBy: 'company', orderBy: ['issuedAt' => 'DESC'])]
    private CollectionInterface $invoices;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->establishments = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->paymentMethods = new ArrayCollection();
        $this->invoices = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLegalName(): ?string
    {
        return $this->legalName;
    }

    public function setLegalName(?string $legalName): static
    {
        $this->legalName = $legalName;

        return $this;
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(?string $siren): static
    {
        $this->siren = $siren;

        return $this;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): static
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    public function getKind(): CompanyKind
    {
        return $this->kind;
    }

    public function setKind(CompanyKind $kind): static
    {
        $this->kind = $kind;

        return $this;
    }

    public function getStatus(): CompanyStatus
    {
        return $this->status;
    }

    public function setStatus(CompanyStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;

        return $this;
    }

    public function getVerifiedAt(): ?DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?DateTimeImmutable $verifiedAt): static
    {
        $this->verifiedAt = $verifiedAt;

        return $this;
    }

    public function getConfig(): ?CompanyConfig
    {
        return $this->config;
    }

    public function setConfig(?CompanyConfig $config): static
    {
        $this->config = $config;

        if ($config !== null && $config->getCompany() !== $this) {
            $config->setCompany($this);
        }

        return $this;
    }

    public function getMembers(): CollectionInterface
    {
        return $this->members;
    }

    public function addMember(CompanyMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setCompany($this);
        }

        return $this;
    }

    public function removeMember(CompanyMember $member): static
    {
        if ($this->members->removeElement($member) && $member->getCompany() === $this) {
            $member->setCompany(null);
        }

        return $this;
    }

    public function getInvitations(): CollectionInterface
    {
        return $this->invitations;
    }

    public function addInvitation(CompanyInvitation $invitation): static
    {
        if (!$this->invitations->contains($invitation)) {
            $this->invitations->add($invitation);
            $invitation->setCompany($this);
        }

        return $this;
    }

    public function removeInvitation(CompanyInvitation $invitation): static
    {
        if ($this->invitations->removeElement($invitation) && $invitation->getCompany() === $this) {
            $invitation->setCompany(null);
        }

        return $this;
    }

    public function getEstablishments(): CollectionInterface
    {
        return $this->establishments;
    }

    public function addEstablishment(CompanyEstablishment $establishment): static
    {
        if (!$this->establishments->contains($establishment)) {
            $this->establishments->add($establishment);
            $establishment->setCompany($this);
        }

        return $this;
    }

    public function removeEstablishment(CompanyEstablishment $establishment): static
    {
        if ($this->establishments->removeElement($establishment) && $establishment->getCompany() === $this) {
            $establishment->setCompany(null);
        }

        return $this;
    }

    public function getSubscriptions(): CollectionInterface
    {
        return $this->subscriptions;
    }

    public function addSubscription(CompanySubscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setCompany($this);
        }

        return $this;
    }

    public function removeSubscription(CompanySubscription $subscription): static
    {
        $this->subscriptions->removeElement($subscription);

        return $this;
    }

    public function getPaymentMethods(): CollectionInterface
    {
        return $this->paymentMethods;
    }

    public function addPaymentMethod(CompanyPaymentMethod $paymentMethod): static
    {
        if (!$this->paymentMethods->contains($paymentMethod)) {
            $this->paymentMethods->add($paymentMethod);
            $paymentMethod->setCompany($this);
        }

        return $this;
    }

    public function removePaymentMethod(CompanyPaymentMethod $paymentMethod): static
    {
        if ($this->paymentMethods->removeElement($paymentMethod) && $paymentMethod->getCompany() === $this) {
            $paymentMethod->setCompany(null);
        }

        return $this;
    }

    public function getInvoices(): CollectionInterface
    {
        return $this->invoices;
    }

    public function addInvoice(CompanyInvoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setCompany($this);
        }

        return $this;
    }

    public function removeInvoice(CompanyInvoice $invoice): static
    {
        $this->invoices->removeElement($invoice);

        return $this;
    }
}