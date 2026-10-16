<?php

namespace App\Controller\Admin;

use App\Entity\Website\WebsiteConfig;
use App\Repository\Website\WebsiteConfigRepository;
use App\Shared\Helper\DateHelper;
use App\Website\Dto\WebsiteIdentityInput;
use App\Website\Dto\WebsiteScheduleInput;
use App\Website\Presenter\WebsiteConfigPresenter;
use App\Website\Presenter\WebsitePricingPresenter;
use App\Website\Presenter\WebsiteRulePresenter;
use App\Website\Provider\WebsiteProvider;
use App\Website\Service\WebsiteVersionService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class WebsiteController extends AbstractAdminController
{
    public function __construct(
        private WebsiteProvider $provider,
        private WebsiteConfigRepository $websites,
        private WebsiteVersionService $versions,
        private WebsiteConfigPresenter $presenter,
        private WebsiteRulePresenter $rulePresenter,
        private WebsitePricingPresenter $pricingPresenter,
    ) {
    }

    #[Route('/admin/versions', name: 'admin_website_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin();

        return $this->page($this->findVersion($this->provider, $request->query->getString('version') ?: null));
    }

    #[Route('/admin/versions/{code}', name: 'admin_website_show', methods: ['GET'])]
    public function show(string $code): Response
    {
        $this->currentAdmin();

        return $this->page($this->findVersion($this->provider, $code));
    }

    #[Csrf('admin_website')]
    #[Route('/admin/versions/{code}/duplicate', name: 'admin_website_duplicate', methods: ['POST'])]
    public function duplicate(string $code): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->findVersion($this->provider, $code);
        $copy = null;

        $this->attempt(function () use ($website, $admin, &$copy): void {
            $copy = $this->versions->duplicate($website, $admin);
        }, sprintf('Version %s dupliquée en brouillon.', $website->getCode()));

        return $this->back($copy?->getCode() ?? $code);
    }

    #[Csrf('admin_website')]
    #[Route('/admin/versions/{code}/identity', name: 'admin_website_update', methods: ['POST'])]
    public function update(string $code, #[MapRequestPayload(validate: false)] WebsiteIdentityInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->findVersion($this->provider, $code);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->versions->updateIdentity($website, $input, $admin), 'Identité de la version enregistrée.');
        }

        return $this->back($code);
    }

    #[Csrf('admin_website')]
    #[Route('/admin/versions/{code}/schedule', name: 'admin_website_schedule', methods: ['POST'])]
    public function schedule(string $code, #[MapRequestPayload(validate: false)] WebsiteScheduleInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->findVersion($this->provider, $code);
        $date = DateHelper::parse($input->scheduledAt);

        if ($this->invalid($input)) {
            return $this->back($code);
        }

        if ($date === null) {
            $this->addFlash('error', 'La date de mise en ligne est invalide.');

            return $this->back($code);
        }

        $this->attempt(fn () => $this->versions->schedule($website, $date, $admin), sprintf('Mise en ligne planifiée le %s.', $date->format('d/m/Y à H:i')));

        return $this->back($code);
    }

    #[Csrf('admin_website')]
    #[Route('/admin/versions/{code}/unschedule', name: 'admin_website_unschedule', methods: ['POST'])]
    public function unschedule(string $code): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->findVersion($this->provider, $code);
        $this->attempt(fn () => $this->versions->unschedule($website, $admin), 'Planification annulée. La version repasse en brouillon.');

        return $this->back($code);
    }

    #[Csrf('admin_website')]
    #[Route('/admin/versions/{code}/publish', name: 'admin_website_publish', methods: ['POST'])]
    public function publish(string $code): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->findVersion($this->provider, $code);
        $this->attempt(fn () => $this->versions->publish($website, $admin), sprintf('Version %s mise en ligne.', $website->getCode()));

        return $this->back($code);
    }

    #[Csrf('admin_website')]
    #[Route('/admin/versions/{code}/restore', name: 'admin_website_restore', methods: ['POST'])]
    public function restore(string $code): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->findVersion($this->provider, $code);
        $restored = null;

        $this->attempt(function () use ($website, $admin, &$restored): void {
            $restored = $this->versions->restore($website, $admin);
        }, sprintf('Retour à la version %s : sa copie est en ligne.', $website->getCode()));

        return $this->back($restored?->getCode() ?? $code);
    }

    #[Csrf('admin_website')]
    #[Route('/admin/versions/{code}/delete', name: 'admin_website_delete', methods: ['POST'])]
    public function delete(string $code): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->findVersion($this->provider, $code);

        if ($this->attempt(fn () => $this->versions->delete($website, $admin), sprintf('Version %s supprimée.', $code))) {
            return $this->redirectToRoute('admin_website_index');
        }

        return $this->back($code);
    }

    private function page(WebsiteConfig $website): Response
    {
        $live = $this->websites->findLive();

        return $this->render('admin/website/index.html.twig', [
            'versions' => $this->presenter->summaries($this->websites->findAllForAdmin()),
            'version' => $this->presenter->detail($website, $live),
            'rules' => $this->rulePresenter->present($website->getRule(), $live?->getRule()),
            'pricing' => $this->pricingPresenter->present($website, $live),
        ]);
    }

    private function back(string $code): RedirectResponse
    {
        return $this->redirectToRoute('admin_website_show', ['code' => $code]);
    }
}