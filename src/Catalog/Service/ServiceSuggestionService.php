<?php

namespace App\Catalog\Service;

use App\Catalog\Dto\ServiceSuggestionInput;
use App\Entity\Category;
use App\Entity\CategoryServiceSuggestion;
use App\Entity\User\User;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use NeoPHP\Package\Orm\OrmManagerInterface;

class ServiceSuggestionService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private ActivityLogger $logger,
    ) {
    }

    public function add(
        Category $category,
        ServiceSuggestionInput $input,
        ?User $actor = null,
    ): CategoryServiceSuggestion {
        $suggestion = (new CategoryServiceSuggestion())->setPosition($category->getServiceSuggestions()->count());
        $this->apply($suggestion, $input);

        $category->addServiceSuggestion($suggestion);
        $this->orm->persist($suggestion);

        $message = sprintf('Suggestion « %s » ajoutée à %s', $suggestion->getName(), $category->getName());
        $this->logger->log(ActivityType::CATALOG, $message, $actor, null, $category);
        $this->orm->flush();

        return $suggestion;
    }

    public function update(
        CategoryServiceSuggestion $suggestion,
        ServiceSuggestionInput $input,
        ?User $actor = null,
    ): CategoryServiceSuggestion {
        $this->apply($suggestion, $input);

        $category = $suggestion->getCategory();
        $message = sprintf('Suggestion « %s » modifiée (%s)', $suggestion->getName(), $category?->getName());
        $this->logger->log(ActivityType::CATALOG, $message, $actor, null, $category);
        $this->orm->flush();

        return $suggestion;
    }

    public function delete(CategoryServiceSuggestion $suggestion, ?User $actor = null): void
    {
        $category = $suggestion->getCategory();
        $message = sprintf('Suggestion « %s » supprimée (%s)', $suggestion->getName(), $category?->getName());

        $category?->removeServiceSuggestion($suggestion);
        $this->orm->remove($suggestion);
        $this->logger->log(ActivityType::CATALOG, $message, $actor, null, $category);
        $this->orm->flush();
    }

    private function apply(CategoryServiceSuggestion $suggestion, ServiceSuggestionInput $input): void
    {
        $suggestion
            ->setName(trim((string) $input->name))
            ->setDuration((int) $input->duration)
            ->setPrice((int) round((float) $input->price * 100));
    }
}