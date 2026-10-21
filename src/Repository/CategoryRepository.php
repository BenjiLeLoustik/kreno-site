<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CategoryRepository extends AbstractRepository
{
    protected string $entityClass = Category::class;

    public function findVisible(string $locale): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.translations', 't')
            ->where('c.visible = :visible')
            ->andWhere('t.locale = :locale')
            ->setParameter('visible', true)
            ->setParameter('locale', $locale)
            ->orderBy('t.name', 'ASC')
            ->addOrderBy('c.position', 'ASC')
            ->getResult();
    }

    public function findOneBySlug(string $slug, string $locale): ?Category
    {
        return $this->createQueryBuilder('c')
            ->join('c.translations', 't')
            ->where('t.slug = :slug')
            ->andWhere('t.locale = :locale')
            ->setParameter('slug', $slug)
            ->setParameter('locale', $locale)
            ->getOneOrNullResult();
    }

    public function findOneVisibleBySlug(string $slug, string $locale): ?Category
    {
        return $this->createQueryBuilder('c')
            ->join('c.translations', 't')
            ->where('t.slug = :slug')
            ->andWhere('t.locale = :locale')
            ->andWhere('c.visible = :visible')
            ->setParameter('slug', $slug)
            ->setParameter('locale', $locale)
            ->setParameter('visible', true)
            ->getOneOrNullResult();
    }

    public function findAllForAdmin(): array
    {
        return $this->findAll(['position' => 'ASC']);
    }

    public function nextPosition(): int
    {
        return $this->count();
    }

    public function findOneByAnySlug(string $slug): ?Category
    {
        return $this->createQueryBuilder('c')
            ->join('c.translations', 't')
            ->where('t.slug = :slug')
            ->setParameter('slug', $slug)
            ->getOneOrNullResult();
    }
}