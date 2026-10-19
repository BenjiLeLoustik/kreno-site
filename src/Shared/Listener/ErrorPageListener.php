<?php

namespace App\Shared\Listener;

use App\Entity\User\User;
use App\Log\Service\AppLogRecorder;
use App\Shared\Error\Context\Context403;
use App\Shared\Error\Context\Context404;
use App\Shared\Error\Context\Context500;
use App\Shared\Error\Context\ContextDefault;
use App\Shared\Error\Context\ContextExpired;
use App\Shared\Error\Context\ErrorContextInterface;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Component\Exception\ExceptionManager;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Kernel\Event\ExceptionEvent;
use NeoPHP\Component\View\ViewManagerInterface;
use NeoPHP\Package\Security\SecurityManagerInterface;
use Throwable;

class ErrorPageListener
{
    public const CONTEXTS = [
        ContextExpired::class,
        Context403::class,
        Context404::class,
        Context500::class,
        ContextDefault::class,
    ];

    public const SERVER_ERROR = 500;

    public const TEMPLATE = 'errors/error.html.twig';

    public function __construct(
        private ViewManagerInterface $view,
        private ExceptionManager $exceptions,
        private AppLogRecorder $recorder,
        private SecurityManagerInterface $security,
    ) {
    }

    #[AsListener(ExceptionEvent::class, priority: -100)]
    public function onException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        $status = $this->exceptions->getStatusCode($throwable);

        if ($status >= self::SERVER_ERROR) {
            $this->record($event, $throwable, $status);
        }

        $context = $this->context($throwable, $status);

        if ($context === null || !$this->shouldRender($event, $status)) {
            return;
        }

        $content = $this->render($status, $context);

        if ($content === null) {
            return;
        }

        $event->setResponse(new Response($content, $status, $this->exceptions->getHeaders($throwable)));
    }

    private function record(ExceptionEvent $event, Throwable $throwable, int $status): void
    {
        $root = $event->getKernel()->getRootPath();

        $this->recorder->record($throwable, $event->getRequest(), $status, $this->userId(), $root);
    }

    private function shouldRender(ExceptionEvent $event, int $status): bool
    {
        $request = $event->getRequest();

        if ($request->wantsJson() || $request->isJson()) {
            return false;
        }

        return $status < self::SERVER_ERROR || !$event->getKernel()->isDebug();
    }

    private function render(int $status, ErrorContextInterface $context): ?string
    {
        try {
            return $this->view->render(self::TEMPLATE, [
                'status' => $status,
                'error' => $context,
            ]);
        } catch (Throwable $exception) {
            error_log($this->failure($exception));

            return null;
        }
    }

    private function failure(Throwable $exception): string
    {
        return sprintf(
            '[ErrorPageListener] %s: %s in %s:%d',
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
        );
    }

    private function context(Throwable $throwable, int $status): ?ErrorContextInterface
    {
        foreach (self::CONTEXTS as $class) {
            $context = new $class();

            if ($context->supports($throwable, $status)) {
                return $context;
            }
        }

        return null;
    }

    private function userId(): ?int
    {
        try {
            $user = $this->security->getUser();
        } catch (Throwable) {
            return null;
        }

        if (!$user instanceof User) {
            return null;
        }

        return $user->getId();
    }
}