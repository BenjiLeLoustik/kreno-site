<?php

declare(strict_types=1);

namespace App\Entity\Company;

use App\Billing\Enum\InvoiceStatus;
use App\Billing\Enum\InvoiceType;
use App\Entity\AbstractBaseEntity;
use App\Repository\Company\CompanyInvoiceRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CompanyInvoiceRepository::class)]
#[ORM\Index(columns: ['company', 'status'])]
#[ORM\Index(columns: ['status', 'dueAt'])]
class CompanyInvoice extends AbstractBaseEntity
{
    #[ORM\ManyToOne(Company::class, inversedBy: 'invoices', nullable: false)]
    private ?Company $company = null;

    #[ORM\ManyToOne(CompanySubscription::class, onDelete: 'SET NULL')]
    private ?CompanySubscription $subscription = null;

    #[ORM\ManyToOne(CompanyInvoice::class, onDelete: 'SET NULL')]
    private ?CompanyInvoice $creditedInvoice = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $number = null;

    #[ORM\Column]
    private InvoiceType $type = InvoiceType::INVOICE;

    #[ORM\Column]
    private InvoiceStatus $status = InvoiceStatus::DRAFT;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $periodStart = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $periodEnd = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $issuedAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $dueAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $paidAt = null;

    #[ORM\Column]
    private int $totalExcludingTax = 0;

    #[ORM\Column(type: 'smallint')]
    private int $taxRate = 20;

    #[ORM\Column]
    private int $taxAmount = 0;

    #[ORM\Column]
    private int $totalIncludingTax = 0;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $billingName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $billingAddress = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $billingVatNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfPath = null;

    #[ORM\OneToMany(CompanyInvoiceLine::class, mappedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true, orderBy: ['position' => 'ASC'])]
    private CollectionInterface $lines;

    #[ORM\OneToMany(CompanyPayment::class, mappedBy: 'invoice', cascade: ['persist'], orderBy: ['createdAt' => 'DESC'])]
    private CollectionInterface $payments;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
        $this->payments = new ArrayCollection();
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getSubscription(): ?CompanySubscription
    {
        return $this->subscription;
    }

    public function setSubscription(?CompanySubscription $subscription): static
    {
        $this->subscription = $subscription;

        return $this;
    }

    public function getCreditedInvoice(): ?CompanyInvoice
    {
        return $this->creditedInvoice;
    }

    public function setCreditedInvoice(?CompanyInvoice $creditedInvoice): static
    {
        $this->creditedInvoice = $creditedInvoice;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getType(): InvoiceType
    {
        return $this->type;
    }

    public function setType(InvoiceType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): InvoiceStatus
    {
        return $this->status;
    }

    public function setStatus(InvoiceStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPeriodStart(): ?DateTimeImmutable
    {
        return $this->periodStart;
    }

    public function setPeriodStart(?DateTimeImmutable $periodStart): static
    {
        $this->periodStart = $periodStart;

        return $this;
    }

    public function getPeriodEnd(): ?DateTimeImmutable
    {
        return $this->periodEnd;
    }

    public function setPeriodEnd(?DateTimeImmutable $periodEnd): static
    {
        $this->periodEnd = $periodEnd;

        return $this;
    }

    public function getIssuedAt(): ?DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(?DateTimeImmutable $issuedAt): static
    {
        $this->issuedAt = $issuedAt;

        return $this;
    }

    public function getDueAt(): ?DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function setDueAt(?DateTimeImmutable $dueAt): static
    {
        $this->dueAt = $dueAt;

        return $this;
    }

    public function getPaidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getTotalExcludingTax(): int
    {
        return $this->totalExcludingTax;
    }

    public function setTotalExcludingTax(int $totalExcludingTax): static
    {
        $this->totalExcludingTax = $totalExcludingTax;

        return $this;
    }

    public function getTaxRate(): int
    {
        return $this->taxRate;
    }

    public function setTaxRate(int $taxRate): static
    {
        $this->taxRate = $taxRate;

        return $this;
    }

    public function getTaxAmount(): int
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(int $taxAmount): static
    {
        $this->taxAmount = $taxAmount;

        return $this;
    }

    public function getTotalIncludingTax(): int
    {
        return $this->totalIncludingTax;
    }

    public function setTotalIncludingTax(int $totalIncludingTax): static
    {
        $this->totalIncludingTax = $totalIncludingTax;

        return $this;
    }

    public function getBillingName(): ?string
    {
        return $this->billingName;
    }

    public function setBillingName(?string $billingName): static
    {
        $this->billingName = $billingName;

        return $this;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?string $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getBillingVatNumber(): ?string
    {
        return $this->billingVatNumber;
    }

    public function setBillingVatNumber(?string $billingVatNumber): static
    {
        $this->billingVatNumber = $billingVatNumber;

        return $this;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): static
    {
        $this->pdfPath = $pdfPath;

        return $this;
    }

    public function getLines(): CollectionInterface
    {
        return $this->lines;
    }

    public function addLine(CompanyInvoiceLine $line): static
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
            $line->setInvoice($this);
        }

        return $this;
    }

    public function removeLine(CompanyInvoiceLine $line): static
    {
        if ($this->lines->removeElement($line) && $line->getInvoice() === $this) {
            $line->setInvoice(null);
        }

        return $this;
    }

    public function getPayments(): CollectionInterface
    {
        return $this->payments;
    }

    public function addPayment(CompanyPayment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setInvoice($this);
        }

        return $this;
    }

    public function removePayment(CompanyPayment $payment): static
    {
        $this->payments->removeElement($payment);

        return $this;
    }
}