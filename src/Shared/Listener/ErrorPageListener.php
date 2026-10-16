<?php

namespace App\Shared\Listener;

use NeoPHP\Component\Csrf\Exception\InvalidCsrfTokenException;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Component\Exception\ExceptionManager;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Kernel\Event\ExceptionEvent;
use NeoPHP\Component\View\ViewManagerInterface;
use Throwable;

class ErrorPageListener
{
    public const PAGES = [
        403 => ['label' => 'accès réservé', 'name' => 'Accès refusé', 'hint' => 'Ce compte n\'a pas accès à cette page.', 'title' => 'Cette porte est fermée.', 'text' => 'Vous n\'avez pas accès à cette page avec ce compte. Connectez-vous avec le bon compte ou revenez à l\'accueil.'],
        404 => ['label' => 'créneau perdu', 'name' => 'Page introuvable', 'hint' => 'Ce lien a expiré ou n\'a jamais existé.', 'title' => 'Ce créneau‑là est déjà parti.', 'text' => 'La page que vous cherchez n\'existe pas ou plus. Un créneau réservé ou expiré disparaît aussi du site.'],
        500 => ['label' => 'imprévu', 'name' => 'Erreur technique', 'hint' => 'Nous sommes prévenus.', 'title' => 'Un imprévu de notre côté.', 'text' => 'Réessayez dans quelques instants. Si le problème continue, écrivez-nous depuis la page d\'aide.'],
    ];

    public const EXPIRED = ['label' => 'page expirée', 'name' => 'Page expirée', 'hint' => 'Le formulaire est resté ouvert trop longtemps.', 'title' => 'La page a expiré.', 'text' => 'Pour votre sécurité, le formulaire n\'a pas été envoyé. Rechargez la page puis réessayez.'];

    public const OTHER = ['label' => 'demande refusée', 'name' => 'Demande impossible', 'hint' => 'Cette action n\'a pas abouti.', 'title' => 'Cette demande n\'a pas abouti.', 'text' => 'Revenez en arrière et réessayez. Si le problème continue, écrivez-nous depuis la page d\'aide.'];

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

        if ($request->wantsJson() || $request->isJson() || $status < 400 || ($status >= 500 && $event->getKernel()->isDebug())) {
            return;
        }

        try {
            $content = $this->view->render('errors/error.html.twig', [
                'status' => $status,
                'page' => $this->page($throwable, $status),
            ]);
        } catch (Throwable) {
            return;
        }

        $event->setResponse(new Response($content, $status, $this->exceptions->getHeaders($throwable)));
    }

    private function page(Throwable $throwable, int $status): array
    {
        if ($throwable instanceof InvalidCsrfTokenException) {
            return self::EXPIRED;
        }

        return self::PAGES[$status] ?? ($status >= 500 ? self::PAGES[500] : self::OTHER);
    }
}