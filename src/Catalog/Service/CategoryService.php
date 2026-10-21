<?php

namespace App\Catalog\Service;

use App\Catalog\Dto\CategoryInput;
use App\Catalog\Exception\CategoryNameRequiredException;
use App\Catalog\Exception\CategoryNotEmptyException;
use App\Entity\Category;
use App\Entity\CategoryTranslation;
use App\Entity\User\User;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Repository\CategoryRepository;
use App\Repository\CategoryTranslationRepository;
use App\Shared\Helper\StringHelper;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class CategoryService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private CategoryRepository $categories,
        private CategoryTranslationRepository $translations,
        private TranslationManagerInterface $translation,
        private ActivityLogger $logger,
    ) {
    }

    public function create(CategoryInput $input, ?User $actor = null, ?string $locale = null): Category
    {
        $name = $this->name($input->name) ?? throw new CategoryNameRequiredException();
        $locale ??= $this->translation->getLocale();

        $category = (new Category())
            ->setVisible($input->visible ?? false)
            ->setPosition($input->position ?? $this->categories->nextPosition());

        if ($input->icon !== null) {
            $category->setIcon($input->icon ?: null);
        }

        $translation = new CategoryTranslation();
        $translation
            ->setLocale($locale)
            ->setName($name)
            ->setDescription($this->description($input->description));

        $category->addTranslation($translation);
        $this->orm->persist($category);

        $visibility = $this->visibility($category->isVisible());
        $message = sprintf('Catégorie %s ajoutée (%s)', $translation->getName(), $visibility);
        $this->logger->log(ActivityType::CATALOG, $message, $actor, null, $category);
        $this->orm->flush();

        return $category;
    }

    public function update(Category $category, CategoryInput $input, ?User $actor = null, ?string $locale = null): Category
    {
        $locale ??= $this->translation->getLocale();
        $translation = $this->translations->findOneByCategoryAndLocale($category, $locale);

        if ($translation === null) {
            $translation = new CategoryTranslation();
            $translation->setLocale($locale);
            $category->addTranslation($translation);
        }

        $name = $this->name($input->name);

        if ($name !== null && $name !== $translation->getName()) {
            $translation->setName($name);
            $translation->setSlug($this->uniqueSlug($name, $locale, $translation->getId()));
        }

        if ($input->description !== null) {
            $translation->setDescription($this->description($input->description));
        }

        if ($input->icon !== null) {
            $category->setIcon($input->icon ?: null);
        }

        if ($input->position !== null) {
            $category->setPosition($input->position);
        }

        if ($input->visible !== null && $input->visible !== $category->isVisible()) {
            return $this->setVisible($category, $input->visible, $actor);
        }

        $message = sprintf('Catégorie %s modifiée', $translation->getName());
        $this->logger->log(ActivityType::CATALOG, $message, $actor, null, $category);
        $this->orm->flush();

        return $category;
    }

    public function setVisible(Category $category, bool $visible, ?User $actor = null): Category
    {
        if ($category->isVisible() === $visible) {
            return $category;
        }

        $category->setVisible($visible);

        $message = sprintf('Catégorie %s %s', $category->getTranslations()->first()?->getName(), $this->visibilityChange($visible));
        $this->logger->log(ActivityType::CATALOG, $message, $actor, null, $category);
        $this->orm->flush();

        return $category;
    }

    public function delete(Category $category, ?User $actor = null): void
    {
        if ($this->establishmentsCount($category) > 0) {
            throw new CategoryNotEmptyException((string) $category->getTranslations()->first()?->getName());
        }

        $this->logger->log(
            ActivityType::CATALOG,
            sprintf('Catégorie %s supprimée', $category->getTranslations()->first()?->getName()),
            $actor
        );
        $this->orm->remove($category);
        $this->orm->flush();
    }

    public function reorder(array $slugs, ?User $actor = null): void
    {
        foreach (array_values($slugs) as $position => $slug) {
            $this->categories->findOneBySlug($slug, $this->translation->getLocale())?->setPosition($position);
        }

        $this->logger->log(ActivityType::CATALOG, 'Ordre des catégories modifié', $actor);
        $this->orm->flush();
    }

    private function establishmentsCount(Category $category): int
    {
        return $category->getEstablishments()->count();
    }

    private function name(?string $name): ?string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return $name;
    }

    private function description(?string $description): ?string
    {
        $description = trim((string) $description);

        if ($description === '') {
            return null;
        }

        return $description;
    }

    private function uniqueSlug(string $name, string $locale, ?int $excludeId = null): string
    {
        return StringHelper::uniqueSlug(
            $name,
            fn (string $slug): bool => $this->translations->slugExists($slug, $locale, $excludeId)
        );
    }

    private function visibility(bool $visible): string
    {
        if ($visible) {
            return 'visible';
        }

        return 'masquée';
    }

    private function visibilityChange(bool $visible): string
    {
        if ($visible) {
            return 'affichée';
        }

        return 'masquée';
    }
}