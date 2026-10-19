<?php

namespace App\Website\Task;

use App\Entity\Website\WebsiteConfig;
use App\Website\Service\WebsiteVersionService;
use DateTimeImmutable;
use NeoPHP\Package\Scheduler\Attribute\AsScheduledTask;

#[AsScheduledTask(
    cron: '* * * * *',
    name: 'website-publish-scheduled',
    withoutOverlapping: true,
    description: 'Met en ligne les versions du site planifiées'
)]
final class PublishScheduledWebsiteTask
{
    public function __invoke(WebsiteVersionService $versions): string
    {
        $published = $versions->publishDue(new DateTimeImmutable());

        if ($published === []) {
            return 'Aucune version à mettre en ligne';
        }

        return 'En ligne : ' . implode(', ', array_map(static fn (WebsiteConfig $website): string => (string) $website->getCode(), $published));
    }
}