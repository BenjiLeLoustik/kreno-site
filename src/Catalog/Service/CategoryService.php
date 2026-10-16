<?php

namespace App\Catalog\Service;

use App\Catalog\Dto\CategoryInput;
use App\Entity\Category;
use App\Entity\User\User;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Repository\CategoryRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Shared\Exception\BusinessRuleException;
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
        if ($input->name === null || trim($input->name) === '') {
            throw new BusinessRuleException('Le nom de la catégorie est obligatoire.');
        }

        $category = (new Category())
            ->setName(trim($input->name))
            ->setVisible($input->visible ?? false)
            ->setPosition($input->position ?? $this->categories->nextPosition());

        $this->apply($category, $input);

        $this->orm->persist($category);
        $this->logger->log(ActivityType::CATALOG, sprintf('Catégorie %s ajoutée (%s)', $category->getName(), $category->isVisible() ? 'visible' : 'masquée'), $actor, null, $category);
        $this->orm->flush();

        return $category;
    }

    public function update(Category $category, CategoryInput $input, ?User $actor = null): Category
    {
        if ($input->name !== null && trim($input->name) !== '') {
            $category->setName(trim($input->name));
        }

        $this->apply($category, $input);

        if ($input->visible !== null && $input->visible !== $category->isVisible()) {
            return $this->setVisible($category, $input->visible, $actor);
        }

        $this->logger->log(ActivityType::CATALOG, sprintf('Catégorie %s modifiée', $category->getName()), $actor, null, $category);
        $this->orm->flush();

        return $category;
    }

    public function setVisible(Category $category, bool $visible, ?User $actor = null): Category
    {
        if ($category->isVisible() === $visible) {
            return $category;
        }

        $category->setVisible($visible);
        $this->logger->log(ActivityType::CATALOG, sprintf('Catégorie %s %s', $category->getName(), $visible ? 'affichée' : 'masquée'), $actor, null, $category);
        $this->orm->flush();

        return $category;
    }

    public function delete(Category $category, ?User $actor = null): void
    {
        if ($this->establishments->countByCategory($category) > 0) {
            throw new BusinessRuleException('{category} contient des établissements : masquez-la plutôt que de la supprimer.', ['category' => $category->getName()]);
        }

        $this->logger->log(ActivityType::CATALOG, sprintf('Catégorie %s supprimée', $category->getName()), $actor);
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