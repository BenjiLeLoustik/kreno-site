<?php

namespace App\Shared\Listener;

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

    public function __construct(
        private ViewManagerInterface $view,
        private ExceptionManager $exceptions,
    ) {
    }

    #[AsListener(ExceptionEvent::class, priority: -100)]
    public function onException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $throwable = $event->getThrowable();
        $status = $this->exceptions->getStatusCode($throwable);
        $context = $this->context($throwable, $status);

        if ($context === null || $request->wantsJson() || $request->isJson() || ($status >= 500 && $event->getKernel()->isDebug())) {
            return;
        }

        try {
            $content = $this->view->render('errors/error.html.twig', [
                'status' => $status,
                'error' => $context,
            ]);
        } catch (Throwable $exception) {
            error_log(sprintf('[ErrorPageListener] %s: %s in %s:%d', $exception::class, $exception->getMessage(), $exception->getFile(), $exception->getLine()));

            return;
        }

        $event->setResponse(new Response($content, $status, $this->exceptions->getHeaders($throwable)));
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
}