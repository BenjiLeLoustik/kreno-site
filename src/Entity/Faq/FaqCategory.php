<?php

namespace App\Entity\Faq;

use App\Entity\AbstractBaseEntity;
use App\Entity\Trait\PositionTrait;
use App\Faq\Enum\FaqAudience;
use App\Repository\Faq\FaqCategoryRepository;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: FaqCategoryRepository::class)]
#[ORM\Index(columns: ['audience', 'visible', 'position'])]
class FaqCategory extends AbstractBaseEntity
{
    use PositionTrait;

    #[ORM\Column]
    private FaqAudience $audience = FaqAudience::CLIENT;

    #[ORM\Column]
    private bool $visible = true;

    #[ORM\OneToMany(
        FaqQuestion::class,
        mappedBy: 'category',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
        orderBy: ['position' => 'ASC'],
    )]
    private CollectionInterface $questions;

    #[ORM\OneToMany(
        FaqCategoryTranslation::class,
        mappedBy: 'category',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
        orderBy: ['locale' => 'ASC'],
    )]
    private CollectionInterface $translations;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function getAudience(): FaqAudience
    {
        return $this->audience;
    }

    public function setAudience(FaqAudience $audience): static
    {
        $this->audience = $audience;

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

    public function getQuestions(): CollectionInterface
    {
        return $this->questions;
    }

    public function addQuestion(FaqQuestion $question): static
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setCategory($this);
        }

        return $this;
    }

    public function removeQuestion(FaqQuestion $question): static
    {
        if ($this->questions->removeElement($question) && $question->getCategory() === $this) {
            $question->setCategory(null);
        }

        return $this;
    }

    public function getTranslations(): CollectionInterface
    {
        return $this->translations;
    }

    public function addTranslation(FaqCategoryTranslation $translation): static
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setCategory($this);
        }

        return $this;
    }

    public function removeTranslation(FaqCategoryTranslation $translation): static
    {
        if ($this->translations->removeElement($translation) && $translation->getCategory() === $this) {
            $translation->setCategory(null);
        }

        return $this;
    }
}