<?php

namespace App\Entity\Faq;

use App\Entity\AbstractBaseEntity;
use App\Entity\Trait\PositionTrait;
use App\Repository\Faq\FaqQuestionRepository;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: FaqQuestionRepository::class)]
#[ORM\Index(columns: ['category', 'position'])]
class FaqQuestion extends AbstractBaseEntity
{
    use PositionTrait;

    #[ORM\ManyToOne(FaqCategory::class, inversedBy: 'questions', nullable: false, onDelete: 'CASCADE')]
    private ?FaqCategory $category = null;

    #[ORM\Column]
    private bool $visible = true;

    #[ORM\OneToMany(
        FaqQuestionTranslation::class,
        mappedBy: 'faqQuestion',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
        orderBy: ['locale' => 'ASC'],
    )]
    private CollectionInterface $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getCategory(): ?FaqCategory
    {
        return $this->category;
    }

    public function setCategory(?FaqCategory $category): static
    {
        $this->category = $category;

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

    public function getTranslations(): CollectionInterface
    {
        return $this->translations;
    }

    public function addTranslation(FaqQuestionTranslation $translation): static
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setFaqQuestion($this);
        }

        return $this;
    }

    public function removeTranslation(FaqQuestionTranslation $translation): static
    {
        if ($this->translations->removeElement($translation) && $translation->getFaqQuestion() === $this) {
            $translation->setFaqQuestion(null);
        }

        return $this;
    }
}