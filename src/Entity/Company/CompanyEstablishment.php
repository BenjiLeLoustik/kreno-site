<?php

declare(strict_types=1);

namespace App\Entity\Company;

use App\Entity\AbstractBaseEntity;
use App\Entity\Category;
use App\Entity\City;
use App\Entity\Establishment\EstablishmentClosure;
use App\Entity\Establishment\EstablishmentFollower;
use App\Entity\Establishment\EstablishmentImage;
use App\Entity\Establishment\EstablishmentMember;
use App\Entity\Establishment\EstablishmentOpeningHour;
use App\Entity\Establishment\EstablishmentService;
use App\Entity\Establishment\EstablishmentSlot;
use App\Entity\Trait\AddressTrait;
use App\Entity\Trait\SlugTrait;
use App\Establishment\Enum\EstablishmentStatus;
use App\Repository\Company\CompanyEstablishmentRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CompanyEstablishmentRepository::class)]
#[ORM\Index(columns: ['city', 'slug'], unique: true)]
#[ORM\Index(columns: ['status', 'city', 'category'])]
#[ORM\Index(columns: ['latitude', 'longitude'])]
class CompanyEstablishment extends AbstractBaseEntity
{
    use SlugTrait;
    use AddressTrait;

    #[ORM\ManyToOne(Company::class, inversedBy: 'establishments', nullable: false, onDelete: 'CASCADE')]
    private ?Company $company = null;

    #[ORM\ManyToOne(City::class, inversedBy: 'establishments', nullable: false)]
    private ?City $city = null;

    #[ORM\ManyToOne(Category::class, inversedBy: 'establishments', onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\Column(length: 120)]
    private ?string $name = null;

    #[ORM\Column(length: 14, nullable: true, unique: true)]
    private ?string $siret = null;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $apeCode = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $defaultDiscount = null;

    #[ORM\Column]
    private EstablishmentStatus $status = EstablishmentStatus::DRAFT;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $verifiedAt = null;

    #[ORM\OneToMany(EstablishmentMember::class, mappedBy: 'establishment', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $members;

    #[ORM\OneToMany(EstablishmentService::class, mappedBy: 'establishment', cascade: ['persist', 'remove'], orphanRemoval: true, orderBy: ['position' => 'ASC'])]
    private CollectionInterface $services;

    #[ORM\OneToMany(EstablishmentSlot::class, mappedBy: 'establishment', cascade: ['persist'], orderBy: ['startsAt' => 'ASC'])]
    private CollectionInterface $slots;

    #[ORM\OneToMany(EstablishmentImage::class, mappedBy: 'establishment', cascade: ['persist', 'remove'], orphanRemoval: true, orderBy: ['position' => 'ASC'])]
    private CollectionInterface $images;

    #[ORM\OneToMany(EstablishmentOpeningHour::class, mappedBy: 'establishment', cascade: ['persist', 'remove'], orphanRemoval: true, orderBy: ['weekday' => 'ASC', 'opensAt' => 'ASC'])]
    private CollectionInterface $openingHours;

    #[ORM\OneToMany(EstablishmentClosure::class, mappedBy: 'establishment', cascade: ['persist', 'remove'], orphanRemoval: true, orderBy: ['startsOn' => 'ASC'])]
    private CollectionInterface $closures;

    #[ORM\OneToMany(EstablishmentFollower::class, mappedBy: 'establishment', cascade: ['remove'], orphanRemoval: true)]
    private CollectionInterface $followers;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->services = new ArrayCollection();
        $this->slots = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->openingHours = new ArrayCollection();
        $this->closures = new ArrayCollection();
        $this->followers = new ArrayCollection();
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

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
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

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret;

        return $this;
    }

    public function getApeCode(): ?string
    {
        return $this->apeCode;
    }

    public function setApeCode(?string $apeCode): static
    {
        $this->apeCode = $apeCode;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDefaultDiscount(): ?int
    {
        return $this->defaultDiscount;
    }

    public function setDefaultDiscount(?int $defaultDiscount): static
    {
        $this->defaultDiscount = $defaultDiscount;

        return $this;
    }

    public function getStatus(): EstablishmentStatus
    {
        return $this->status;
    }

    public function setStatus(EstablishmentStatus $status): static
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

    public function getMembers(): CollectionInterface
    {
        return $this->members;
    }

    public function addMember(EstablishmentMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setEstablishment($this);
        }

        return $this;
    }

    public function removeMember(EstablishmentMember $member): static
    {
        if ($this->members->removeElement($member) && $member->getEstablishment() === $this) {
            $member->setEstablishment(null);
        }

        return $this;
    }

    public function getServices(): CollectionInterface
    {
        return $this->services;
    }

    public function addService(EstablishmentService $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
            $service->setEstablishment($this);
        }

        return $this;
    }

    public function removeService(EstablishmentService $service): static
    {
        if ($this->services->removeElement($service) && $service->getEstablishment() === $this) {
            $service->setEstablishment(null);
        }

        return $this;
    }

    public function getSlots(): CollectionInterface
    {
        return $this->slots;
    }

    public function addSlot(EstablishmentSlot $slot): static
    {
        if (!$this->slots->contains($slot)) {
            $this->slots->add($slot);
            $slot->setEstablishment($this);
        }

        return $this;
    }

    public function removeSlot(EstablishmentSlot $slot): static
    {
        $this->slots->removeElement($slot);

        return $this;
    }

    public function getImages(): CollectionInterface
    {
        return $this->images;
    }

    public function addImage(EstablishmentImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setEstablishment($this);
        }

        return $this;
    }

    public function removeImage(EstablishmentImage $image): static
    {
        if ($this->images->removeElement($image) && $image->getEstablishment() === $this) {
            $image->setEstablishment(null);
        }

        return $this;
    }

    public function getOpeningHours(): CollectionInterface
    {
        return $this->openingHours;
    }

    public function addOpeningHour(EstablishmentOpeningHour $openingHour): static
    {
        if (!$this->openingHours->contains($openingHour)) {
            $this->openingHours->add($openingHour);
            $openingHour->setEstablishment($this);
        }

        return $this;
    }

    public function removeOpeningHour(EstablishmentOpeningHour $openingHour): static
    {
        if ($this->openingHours->removeElement($openingHour) && $openingHour->getEstablishment() === $this) {
            $openingHour->setEstablishment(null);
        }

        return $this;
    }

    public function getClosures(): CollectionInterface
    {
        return $this->closures;
    }

    public function addClosure(EstablishmentClosure $closure): static
    {
        if (!$this->closures->contains($closure)) {
            $this->closures->add($closure);
            $closure->setEstablishment($this);
        }

        return $this;
    }

    public function removeClosure(EstablishmentClosure $closure): static
    {
        if ($this->closures->removeElement($closure) && $closure->getEstablishment() === $this) {
            $closure->setEstablishment(null);
        }

        return $this;
    }

    public function getFollowers(): CollectionInterface
    {
        return $this->followers;
    }

    public function addFollower(EstablishmentFollower $follower): static
    {
        if (!$this->followers->contains($follower)) {
            $this->followers->add($follower);
            $follower->setEstablishment($this);
        }

        return $this;
    }

    public function removeFollower(EstablishmentFollower $follower): static
    {
        if ($this->followers->removeElement($follower) && $follower->getEstablishment() === $this) {
            $follower->setEstablishment(null);
        }

        return $this;
    }
}