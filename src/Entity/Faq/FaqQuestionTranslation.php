<?php

declare(strict_types=1);

namespace App\Entity\Faq;

use App\Entity\AbstractEntityTranslated;
use App\Repository\Faq\FaqQuestionTranslationRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: FaqQuestionTranslationRepository::class)]
#[ORM\Index(columns: ['faqQuestion', 'locale'], unique: true)]
class FaqQuestionTranslation extends AbstractEntityTranslated
{
    #[ORM\ManyToOne(FaqQuestion::class, inversedBy: 'translations', nullable: false, onDelete: 'CASCADE')]
    private ?FaqQuestion $faqQuestion = null;

    #[ORM\Column(type: 'text')]
    private ?string $answer = null;

    public function getFaqQuestion(): ?FaqQuestion
    {
        return $this->faqQuestion;
    }

    public function setFaqQuestion(?FaqQuestion $faqQuestion): static
    {
        $this->faqQuestion = $faqQuestion;

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
}