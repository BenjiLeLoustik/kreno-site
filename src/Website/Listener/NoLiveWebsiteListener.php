<?php

namespace App\Website\Listener;

use App\Website\Exception\NoLiveWebsiteException;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Kernel\Event\ExceptionEvent;
use NeoPHP\Component\Routing\RoutingManagerInterface;
use Throwable;

class NoLiveWebsiteListener
{
    public function __construct(
        private RoutingManagerInterface $routing,
    ) {
    }

    #[AsListener(ExceptionEvent::class, priority: 100)]
    public function onException(ExceptionEvent $event): void
    {
        if (!$this->isNoLiveWebsite($event->getThrowable())) {
            return;
        }

        $install = $this->routing->generate('app_install');

        if (str_starts_with($event->getRequest()->getPath(), $install)) {
            return;
        }

        $event->setResponse(new RedirectResponse($install));
    }

    private function isNoLiveWebsite(?Throwable $throwable): bool
    {
        while ($throwable !== null) {
            if ($throwable instanceof NoLiveWebsiteException) {
                return true;
            }

            $throwable = $throwable->getPrevious();
        }

        return false;
    }
}