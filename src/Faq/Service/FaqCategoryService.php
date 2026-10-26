<?php

namespace App\Faq\Service;

use App\Entity\Faq\FaqCategory;
use App\Entity\Faq\FaqCategoryTranslation;
use App\Entity\User\User;
use App\Faq\Dto\FaqCategoryInput;
use App\Faq\Enum\FaqAudience;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Repository\Faq\FaqCategoryRepository;
use App\Repository\Faq\FaqCategoryTranslationRepository;
use App\Shared\Helper\StringHelper;
use App\Shared\Service\TranslatableResolver;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Translation\TranslationManagerInterface;

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
        private FaqCategoryTranslationRepository $translations,
        private TranslatableResolver $resolver,
        private TranslationManagerInterface $translation,
        private ActivityLogger $logger,
    ) {
    }

    public function create(FaqCategoryInput $input, ?User $actor = null, ?string $locale = null): FaqCategory
    {
        $audience = FaqAudience::from((string) $input->audience);
        $category = (new FaqCategory())
            ->setAudience($audience)
            ->setPosition($this->categories->nextPosition($audience));

        $translation = (new FaqCategoryTranslation())
            ->setLocale($locale ?? $this->translation->getLocale())
            ->setName(trim((string) $input->name));

        $category->addTranslation($translation);
        $this->orm->persist($category);
        $this->logger->log(
            ActivityType::FAQ,
            sprintf(self::LOG_CREATED, $translation->getName(), $audience->label()),
            $actor,
        );
        $this->orm->flush();

        return $category;
    }

    public function update(
        FaqCategory $category,
        FaqCategoryInput $input,
        ?User $actor = null,
        ?string $locale = null,
    ): FaqCategory {
        $locale ??= $this->translation->getLocale();
        $audience = FaqAudience::from((string) $input->audience);

        if ($audience !== $category->getAudience()) {
            $category->setAudience($audience)->setPosition($this->categories->nextPosition($audience));
        }

        $translation = $this->translations->findOneByCategoryAndLocale($category, $locale);

        if ($translation === null) {
            $translation = (new FaqCategoryTranslation())->setLocale($locale);
            $category->addTranslation($translation);
        }

        $name = trim((string) $input->name);

        if ($name !== $translation->getName()) {
            $translation->setName($name);
            $translation->setSlug($this->uniqueSlug($name, $locale, $translation->getId()));
        }

        $this->logger->log(ActivityType::FAQ, sprintf(self::LOG_UPDATED, $name), $actor);
        $this->orm->flush();

        return $category;
    }

    public function setVisible(FaqCategory $category, bool $visible, ?User $actor = null): FaqCategory
    {
        if ($category->isVisible() === $visible) {
            return $category;
        }

        $category->setVisible($visible);
        $this->logger->log(ActivityType::FAQ, sprintf($this->visibilityLog($visible), $this->name($category)), $actor);
        $this->orm->flush();

        return $category;
    }

    public function delete(FaqCategory $category, ?User $actor = null): void
    {
        $this->logger->log(ActivityType::FAQ, sprintf(self::LOG_DELETED, $this->name($category)), $actor);
        $this->orm->remove($category);
        $this->orm->flush();
    }

    public function reorder(array $slugs, ?User $actor = null): void
    {
        foreach (array_values($slugs) as $position => $slug) {
            $this->categories->findOneByAnySlugOrId((string) $slug)?->setPosition($position);
        }

        $this->logger->log(ActivityType::FAQ, self::LOG_REORDERED, $actor);
        $this->orm->flush();
    }

    private function name(FaqCategory $category): ?string
    {
        return $this->resolver->faqCategory($category)?->getName();
    }

    private function uniqueSlug(string $name, string $locale, ?int $excludeId = null): string
    {
        return StringHelper::uniqueSlug(
            $name,
            fn (string $slug): bool => $this->translations->slugExists($slug, $locale, $excludeId)
        );
    }

    private function visibilityLog(bool $visible): string
    {
        if ($visible) {
            return self::LOG_SHOWN;
        }

        return self::LOG_HIDDEN;
    }
}