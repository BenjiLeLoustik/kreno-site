<?php

namespace App\Entity;

use App\Entity\Trait\PositionTrait;
use App\Repository\CategoryServiceSuggestionRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CategoryServiceSuggestionRepository::class)]
#[ORM\Index(columns: ['category', 'position'])]
class CategoryServiceSuggestion extends AbstractBaseEntity
{
    use PositionTrait;

    #[ORM\ManyToOne(Category::class, inversedBy: 'serviceSuggestions', nullable: false, onDelete: 'CASCADE')]
    private ?Category $category = null;

    #[ORM\Column(length: 120)]
    private ?string $name = null;

    #[ORM\Column(type: 'smallint')]
    private int $duration = 30;

    #[ORM\Column]
    private int $price = 0;

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

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }
}