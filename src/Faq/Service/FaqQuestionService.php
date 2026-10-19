<?php

namespace App\Faq\Service;

use App\Entity\Faq\FaqCategory;
use App\Entity\Faq\FaqQuestion;
use App\Entity\User\User;
use App\Faq\Dto\FaqQuestionInput;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use NeoPHP\Package\Orm\OrmManagerInterface;

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
        private ActivityLogger $logger,
    ) {
    }

    public function add(FaqCategory $category, FaqQuestionInput $input, ?User $actor = null): FaqQuestion
    {
        $question = (new FaqQuestion())->setPosition($category->getQuestions()->count());
        $this->apply($question, $input);
        $category->addQuestion($question);
        $this->orm->persist($question);
        $this->log(self::LOG_CREATED, $question, $actor);
        $this->orm->flush();

        return $question;
    }

    public function update(FaqQuestion $question, FaqQuestionInput $input, ?User $actor = null): FaqQuestion
    {
        $this->apply($question, $input);
        $this->log(self::LOG_UPDATED, $question, $actor);
        $this->orm->flush();

        return $question;
    }

    public function setVisible(FaqQuestion $question, bool $visible, ?User $actor = null): FaqQuestion
    {
        if ($question->isVisible() === $visible) {
            return $question;
        }

        $question->setVisible($visible);
        $this->log($this->visibilityLog($visible), $question, $actor);
        $this->orm->flush();

        return $question;
    }

    public function delete(FaqQuestion $question, ?User $actor = null): void
    {
        $this->log(self::LOG_DELETED, $question, $actor);
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

        $this->logger->log(ActivityType::FAQ, sprintf(self::LOG_REORDERED, $category->getName()), $actor);
        $this->orm->flush();
    }

    private function apply(FaqQuestion $question, FaqQuestionInput $input): void
    {
        $question
            ->setQuestion(trim((string) $input->question))
            ->setAnswer(trim((string) $input->answer));
    }

    private function log(string $pattern, FaqQuestion $question, ?User $actor): void
    {
        $message = sprintf($pattern, $question->getCategory()?->getName(), $question->getQuestion());
        $this->logger->log(ActivityType::FAQ, $message, $actor);
    }

    private function visibilityLog(bool $visible): string
    {
        if ($visible) {
            return self::LOG_SHOWN;
        }

        return self::LOG_HIDDEN;
    }
}