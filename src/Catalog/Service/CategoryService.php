<?php

namespace App\Catalog\Service;

use App\Catalog\Dto\CategoryInput;
use App\Catalog\Exception\CategoryNameRequiredException;
use App\Catalog\Exception\CategoryNotEmptyException;
use App\Entity\Category;
use App\Entity\User\User;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Repository\CategoryRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use NeoPHP\Package\Orm\OrmManagerInterface;

class CategoryService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private CategoryRepository $categories,
        private CompanyEstablishmentRepository $establishments,
        private ActivityLogger $logger,
    ) {
    }

    public function create(CategoryInput $input, ?User $actor = null): Category
    {
        $name = $this->name($input->name) ?? throw new CategoryNameRequiredException();

        $category = (new Category())
            ->setName($name)
            ->setVisible($input->visible ?? false)
            ->setPosition($input->position ?? $this->categories->nextPosition());

        $this->apply($category, $input);
        $this->orm->persist($category);

        $visibility = $this->visibility($category->isVisible());
        $message = sprintf('Catégorie %s ajoutée (%s)', $category->getName(), $visibility);
        $this->logger->log(ActivityType::CATALOG, $message, $actor, null, $category);
        $this->orm->flush();

        return $category;
    }

    public function update(Category $category, CategoryInput $input, ?User $actor = null): Category
    {
        $name = $this->name($input->name);

        if ($name !== null) {
            $category->setName($name);
        }

        $this->apply($category, $input);

        if ($input->visible !== null && $input->visible !== $category->isVisible()) {
            return $this->setVisible($category, $input->visible, $actor);
        }

        $message = sprintf('Catégorie %s modifiée', $category->getName());
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

        $message = sprintf('Catégorie %s %s', $category->getName(), $this->visibilityChange($visible));
        $this->logger->log(ActivityType::CATALOG, $message, $actor, null, $category);
        $this->orm->flush();

        return $category;
    }

    public function delete(Category $category, ?User $actor = null): void
    {
        if ($this->establishments->countByCategory($category) > 0) {
            throw new CategoryNotEmptyException((string) $category->getName());
        }

        $this->logger->log(
            ActivityType::CATALOG,
            sprintf('Catégorie %s supprimée', $category->getName()),
            $actor
        );
        $this->orm->remove($category);
        $this->orm->flush();
    }

    public function reorder(array $slugs, ?User $actor = null): void
    {
        foreach (array_values($slugs) as $position => $slug) {
            $this->categories->findOneBySlug($slug)?->setPosition($position);
        }

        $this->logger->log(ActivityType::CATALOG, 'Ordre des catégories modifié', $actor);
        $this->orm->flush();
    }

    private function name(?string $name): ?string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return $name;
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

    private function apply(Category $category, CategoryInput $input): void
    {
        if ($input->icon !== null) {
            $category->setIcon($input->icon ?: null);
        }

        if ($input->description !== null) {
            $category->setDescription(trim($input->description) ?: null);
        }

        if ($input->position !== null) {
            $category->setPosition($input->position);
        }
    }
}