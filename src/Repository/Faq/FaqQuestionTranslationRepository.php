<?php

declare(strict_types=1);

namespace App\Repository\Faq;

use App\Entity\Faq\FaqQuestion;
use App\Entity\Faq\FaqQuestionTranslation;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class FaqQuestionTranslationRepository extends AbstractRepository
{
    protected string $entityClass = FaqQuestionTranslation::class;

    public function findOneByQuestionAndLocale(FaqQuestion $question, string $locale): ?FaqQuestionTranslation
    {
        return $this->findOneBy([
            'faqQuestion' => $question,
            'locale' => $locale,
        ]);
    }
}