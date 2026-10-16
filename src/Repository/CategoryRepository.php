<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CategoryRepository extends AbstractRepository
{
    protected string $entityClass = Category::class;

    public function findVisible(): array
    {
        return $this->findBy(['visible' => true], ['position' => 'ASC', 'name' => 'ASC']);
    }

    public function findOneBySlug(string $slug): ?Category
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findOneVisibleBySlug(string $slug): ?Category
    {
        return $this->findOneBy(['slug' => $slug, 'visible' => true]);
    }

    public function findAllForAdmin(): array
    {
        return $this->findAll(['position' => 'ASC', 'name' => 'ASC']);
    }

    public function slugExists(string $slug): bool
    {
        return $this->count(['slug' => $slug]) > 0;
    }

    public function nextPosition(): int
    {
        return $this->count();
    }
}