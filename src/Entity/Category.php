<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Trait\PositionTrait;
use App\Entity\Trait\SlugTrait;
use App\Repository\CategoryRepository;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CategoryRepository::class)]
#[ORM\Index(columns: ['slug'], unique: true)]
#[ORM\Index(columns: ['visible', 'position'])]
class Category extends AbstractBaseEntity
{
    use SlugTrait;
    use PositionTrait;

    #[ORM\Column(length: 80)]
    private ?string $name = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $visible = true;

    #[ORM\OneToMany(CompanyEstablishment::class, mappedBy: 'category')]
    private CollectionInterface $establishments;

    #[ORM\OneToMany(CategoryServiceSuggestion::class, mappedBy: 'category', cascade: ['persist', 'remove'], orphanRemoval: true, orderBy: ['position' => 'ASC'])]
    private CollectionInterface $serviceSuggestions;

    public function __construct()
    {
        $this->establishments = new ArrayCollection();
        $this->serviceSuggestions = new ArrayCollection();
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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

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

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

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
            $establishment->setCategory($this);
        }

        return $this;
    }

    public function removeEstablishment(CompanyEstablishment $establishment): static
    {
        if ($this->establishments->removeElement($establishment) && $establishment->getCategory() === $this) {
            $establishment->setCategory(null);
        }

        return $this;
    }

    public function getServiceSuggestions(): CollectionInterface
    {
        return $this->serviceSuggestions;
    }

    public function addServiceSuggestion(CategoryServiceSuggestion $suggestion): static
    {
        if (!$this->serviceSuggestions->contains($suggestion)) {
            $this->serviceSuggestions->add($suggestion);
            $suggestion->setCategory($this);
        }

        return $this;
    }

    public function removeServiceSuggestion(CategoryServiceSuggestion $suggestion): static
    {
        if ($this->serviceSuggestions->removeElement($suggestion) && $suggestion->getCategory() === $this) {
            $suggestion->setCategory(null);
        }

        return $this;
    }
}