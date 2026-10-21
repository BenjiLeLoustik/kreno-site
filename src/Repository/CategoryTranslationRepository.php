<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use App\Entity\CategoryTranslation;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CategoryTranslationRepository extends AbstractRepository
{
    protected string $entityClass = CategoryTranslation::class;

    public function findOneByCategoryAndLocale(Category $category, string $locale): ?CategoryTranslation
    {
        return $this->findOneBy([
            'category' => $category,
            'locale' => $locale,
        ]);
    }

    public function findOneBySlugAndLocale(string $slug, string $locale): ?CategoryTranslation
    {
        return $this->findOneBy([
            'slug' => $slug,
            'locale' => $locale,
        ]);
    }

    public function slugExists(string $slug, string $locale, ?int $excludeId = null): bool
    {
        $builder = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.slug = :slug')
            ->andWhere('t.locale = :locale')
            ->setParameter('slug', $slug)
            ->setParameter('locale', $locale);

        if ($excludeId !== null) {
            $builder
                ->andWhere('t.id != :id')
                ->setParameter('id', $excludeId);
        }

        return (int) $builder->getSingleScalarResult() > 0;
    }
}