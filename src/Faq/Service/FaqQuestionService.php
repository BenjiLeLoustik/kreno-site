<?php

namespace App\Faq\Service;

use App\Entity\Faq\FaqCategory;
use App\Entity\Faq\FaqQuestion;
use App\Entity\Faq\FaqQuestionTranslation;
use App\Entity\User\User;
use App\Faq\Dto\FaqQuestionInput;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Repository\Faq\FaqQuestionTranslationRepository;
use App\Shared\Service\TranslatableResolver;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class FaqQuestionService
{
    public const LOG_CREATED = 'Question FAQ ajoutée à « %s » : %s';

    public const LOG_UPDATED = 'Question FAQ modifiée (%s) : %s';

    public const LOG_SHOWN = 'Question FAQ affichée (%s) : %s';

    public const LOG_HIDDEN = 'Question FAQ masquée (%s) : %s';

    public const LOG_DELETED = 'Question FAQ supprimée (%s) : %s';

    public const LOG_REORDERED = 'Ordre des questions FAQ modifié (%s)';

    public function __construct(
        private OrmManagerInterface $orm,
        private FaqQuestionTranslationRepository $translations,
        private TranslatableResolver $resolver,
        private TranslationManagerInterface $translation,
        private ActivityLogger $logger,
    ) {
    }

    public function add(
        FaqCategory $category,
        FaqQuestionInput $input,
        ?User $actor = null,
        ?string $locale = null,
    ): FaqQuestion {
        $question = (new FaqQuestion())->setPosition($category->getQuestions()->count());
        $translation = (new FaqQuestionTranslation())->setLocale($locale ?? $this->translation->getLocale());
        $this->apply($translation, $input);
        $question->addTranslation($translation);
        $category->addQuestion($question);
        $this->orm->persist($question);
        $this->log(self::LOG_CREATED, $question, $translation->getName(), $actor);
        $this->orm->flush();

        return $question;
    }

    public function update(
        FaqQuestion $question,
        FaqQuestionInput $input,
        ?User $actor = null,
        ?string $locale = null,
    ): FaqQuestion {
        $locale ??= $this->translation->getLocale();
        $translation = $this->translations->findOneByQuestionAndLocale($question, $locale);

        if ($translation === null) {
            $translation = (new FaqQuestionTranslation())->setLocale($locale);
            $question->addTranslation($translation);
        }

        $this->apply($translation, $input);
        $this->log(self::LOG_UPDATED, $question, $translation->getName(), $actor);
        $this->orm->flush();

        return $question;
    }

    public function setVisible(FaqQuestion $question, bool $visible, ?User $actor = null): FaqQuestion
    {
        if ($question->isVisible() === $visible) {
            return $question;
        }

        $question->setVisible($visible);
        $this->log($this->visibilityLog($visible), $question, $this->text($question), $actor);
        $this->orm->flush();

        return $question;
    }

    public function delete(FaqQuestion $question, ?User $actor = null): void
    {
        $this->log(self::LOG_DELETED, $question, $this->text($question), $actor);
        $question->getCategory()?->removeQuestion($question);
        $this->orm->remove($question);
        $this->orm->flush();
    }

    public function reorder(FaqCategory $category, array $ids, ?User $actor = null): void
    {
        $positions = array_flip(array_map('intval', array_values($ids)));

        foreach ($category->getQuestions() as $question) {
            if (isset($positions[$question->getId()])) {
                $question->setPosition($positions[$question->getId()]);
            }
        }

        $message = sprintf(self::LOG_REORDERED, $this->resolver->faqCategory($category)?->getName());
        $this->logger->log(ActivityType::FAQ, $message, $actor);
        $this->orm->flush();
    }

    private function apply(FaqQuestionTranslation $translation, FaqQuestionInput $input): void
    {
        $translation
            ->setName(trim((string) $input->question))
            ->setAnswer(trim((string) $input->answer));
    }

    private function text(FaqQuestion $question): ?string
    {
        return $this->resolver->faqQuestion($question)?->getName();
    }

    private function log(string $pattern, FaqQuestion $question, ?string $text, ?User $actor): void
    {
        $category = $question->getCategory();
        $name = $category === null ? null : $this->resolver->faqCategory($category)?->getName();
        $this->logger->log(ActivityType::FAQ, sprintf($pattern, $name, $text), $actor);
    }

    private function visibilityLog(bool $visible): string
    {
        if ($visible) {
            return self::LOG_SHOWN;
        }

        return self::LOG_HIDDEN;
    }
}