<?php

declare(strict_types=1);

namespace App\Entity\Faq;

use App\Entity\AbstractEntityTranslated;
use App\Repository\Faq\FaqCategoryTranslationRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: FaqCategoryTranslationRepository::class)]
#[ORM\Index(columns: ['locale', 'slug'], unique: true)]
#[ORM\Index(columns: ['category', 'locale'])]
class FaqCategoryTranslation extends AbstractEntityTranslated
{
    #[ORM\ManyToOne(FaqCategory::class, inversedBy: 'translations', nullable: false, onDelete: 'CASCADE')]
    private ?FaqCategory $category = null;

    #[ORM\Column(length: 140)]
    private ?string $slug = null;

    public function getCategory(): ?FaqCategory
    {
        return $this->category;
    }

    public function setCategory(?FaqCategory $category): static
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
}