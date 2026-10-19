<?php

namespace App\Entity\Faq;

use App\Entity\AbstractBaseEntity;
use App\Entity\Trait\PositionTrait;
use App\Repository\Faq\FaqQuestionRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: FaqQuestionRepository::class)]
#[ORM\Index(columns: ['category', 'position'])]
class FaqQuestion extends AbstractBaseEntity
{
    use PositionTrait;

    #[ORM\ManyToOne(FaqCategory::class, inversedBy: 'questions', nullable: false, onDelete: 'CASCADE')]
    private ?FaqCategory $category = null;

    #[ORM\Column(length: 255)]
    private ?string $question = null;

    #[ORM\Column(type: 'text')]
    private ?string $answer = null;

    #[ORM\Column]
    private bool $visible = true;

    public function getCategory(): ?FaqCategory
    {
        return $this->category;
    }

    public function setCategory(?FaqCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): static
    {
        $this->answer = $answer;

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
}