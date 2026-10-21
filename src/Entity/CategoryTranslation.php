<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CategoryTranslationRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CategoryTranslationRepository::class)]
#[ORM\Index(columns: ['locale', 'slug'], unique: true)]
#[ORM\Index(columns: ['category', 'locale'])]
class CategoryTranslation extends AbstractEntityTranslated
{
    #[ORM\ManyToOne(Category::class, inversedBy: 'translations', nullable: false)]
    private ?Category $category = null;

    #[ORM\Column(length: 140)]
    private ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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
}