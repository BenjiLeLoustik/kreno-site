<?php

namespace App\Faq\Service;

use App\Entity\Faq\FaqCategory;
use App\Entity\User\User;
use App\Faq\Dto\FaqCategoryInput;
use App\Faq\Enum\FaqAudience;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Repository\Faq\FaqCategoryRepository;
use NeoPHP\Package\Orm\OrmManagerInterface;

class FaqCategoryService
{
    public const LOG_CREATED = 'Rubrique FAQ « %s » ajoutée (%s)';

    public const LOG_UPDATED = 'Rubrique FAQ « %s » modifiée';

    public const LOG_SHOWN = 'Rubrique FAQ « %s » affichée';

    public const LOG_HIDDEN = 'Rubrique FAQ « %s » masquée';

    public const LOG_DELETED = 'Rubrique FAQ « %s » supprimée';

    public const LOG_REORDERED = 'Ordre des rubriques FAQ modifié';

    public function __construct(
        private OrmManagerInterface $orm,
        private FaqCategoryRepository $categories,
        private ActivityLogger $logger,
    ) {
    }

    public function create(FaqCategoryInput $input, ?User $actor = null): FaqCategory
    {
        $audience = FaqAudience::from((string) $input->audience);
        $category = (new FaqCategory())
            ->setName(trim((string) $input->name))
            ->setAudience($audience)
            ->setPosition($this->categories->nextPosition($audience));

        $this->orm->persist($category);
        $this->logger->log(
            ActivityType::FAQ,
            sprintf(self::LOG_CREATED, $category->getName(), $audience->label()),
            $actor,
        );
        $this->orm->flush();

        return $category;
    }

    public function update(FaqCategory $category, FaqCategoryInput $input, ?User $actor = null): FaqCategory
    {
        $audience = FaqAudience::from((string) $input->audience);

        if ($audience !== $category->getAudience()) {
            $category->setAudience($audience)->setPosition($this->categories->nextPosition($audience));
        }

        $category->setName(trim((string) $input->name));
        $this->logger->log(ActivityType::FAQ, sprintf(self::LOG_UPDATED, $category->getName()), $actor);
        $this->orm->flush();

        return $category;
    }

    public function setVisible(FaqCategory $category, bool $visible, ?User $actor = null): FaqCategory
    {
        if ($category->isVisible() === $visible) {
            return $category;
        }

        $category->setVisible($visible);
        $this->logger->log(ActivityType::FAQ, sprintf($this->visibilityLog($visible), $category->getName()), $actor);
        $this->orm->flush();

        return $category;
    }

    public function delete(FaqCategory $category, ?User $actor = null): void
    {
        $this->logger->log(ActivityType::FAQ, sprintf(self::LOG_DELETED, $category->getName()), $actor);
        $this->orm->remove($category);
        $this->orm->flush();
    }

    public function reorder(array $slugs, ?User $actor = null): void
    {
        foreach (array_values($slugs) as $position => $slug) {
            $this->categories->findOneBySlug((string) $slug)?->setPosition($position);
        }

        $this->logger->log(ActivityType::FAQ, self::LOG_REORDERED, $actor);
        $this->orm->flush();
    }

    private function visibilityLog(bool $visible): string
    {
        if ($visible) {
            return self::LOG_SHOWN;
        }

        return self::LOG_HIDDEN;
    }
}