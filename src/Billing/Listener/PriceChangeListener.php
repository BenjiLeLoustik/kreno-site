<?php

namespace App\Billing\Listener;

use App\Billing\Service\PriceChangeNotifier;
use App\Website\Event\WebsiteVersionPublishedEvent;
use App\Website\Event\WebsiteVersionScheduledEvent;
use NeoPHP\Component\Event\Attribute\AsListener;

class PriceChangeListener
{
    public function __construct(
        private PriceChangeNotifier $notifier,
    ) {
    }

    #[AsListener(WebsiteVersionScheduledEvent::class)]
    public function onScheduled(WebsiteVersionScheduledEvent $event): void
    {
        $this->notifier->announce($event->getWebsite());
    }

    #[AsListener(WebsiteVersionPublishedEvent::class)]
    public function onPublished(WebsiteVersionPublishedEvent $event): void
    {
        if (!$event->wasScheduled()) {
            $this->notifier->announce($event->getWebsite(), $event->getWebsite()->getPublishedAt());
        }
    }
}