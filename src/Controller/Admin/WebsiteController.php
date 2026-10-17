<?php

namespace App\Controller\Admin;

use App\Shared\Mapper\AdminQueryMapper;
use App\Website\Dto\WebsiteIdentityInput;
use App\Website\Dto\WebsiteScheduleInput;
use App\Website\Enum\AdminWebsiteFlash;
use App\Website\Provider\AdminWebsitePageProvider;
use App\Website\Provider\WebsiteVersionProvider;
use App\Website\Service\WebsiteScheduleService;
use App\Website\Service\WebsiteVersionService;
use DateTimeImmutable;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class WebsiteController extends AbstractAdminController
{
    public function __construct(
        private WebsiteVersionProvider $finder,
        private WebsiteVersionService $versions,
        private WebsiteScheduleService $schedules,
        private AdminWebsitePageProvider $pages,
    ) {
    }

    #[Route('/admin/versions', name: 'admin_website_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin();
        $website = $this->finder->find(AdminQueryMapper::optional($request, 'version'));

        return $this->render('admin/website/index.html.twig', $this->pages->page($website));
    }

    #[Route('/admin/versions/{code}', name: 'admin_website_show', methods: ['GET'])]
    public function show(string $code): Response
    {
        $this->currentAdmin();

        return $this->render('admin/website/index.html.twig', $this->pages->page($this->finder->find($code)));
    }

    #[Csrf('admin_website')]
    #[Route('/admin/versions/{code}/duplicate', name: 'admin_website_duplicate', methods: ['POST'])]
    public function duplicate(string $code): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->finder->find($code);
        $copy = null;
        $this->attempt(function () use ($website, $admin, &$copy): void {
            $copy = $this->versions->duplicate($website, $admin);
        }, AdminWebsiteFlash::DUPLICATED->text($website->getCode()));

        return $this->redirectToRoute('admin_website_show', ['code' => $copy?->getCode() ?? $code]);
    }

    #[Csrf('admin_website')]
    #[Route('/admin/versions/{code}/identity', name: 'admin_website_update', methods: ['POST'])]
    public function update(
        string $code,
        #[MapRequestPayload(validate: false)] WebsiteIdentityInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin();
        $website = $this->finder->find($code);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->versions->updateIdentity($website, $input, $admin),
                AdminWebsiteFlash::IDENTITY_SAVED->text(),
            );
        }

        return $this->redirectToRoute('admin_website_show', ['code' => $code]);
    }

    #[Csrf('admin_website')]
    #[Route('/admin/versions/{code}/schedule', name: 'admin_website_schedule', methods: ['POST'])]
    public function schedule(
        string $code,
        #[MapRequestPayload(validate: false)] WebsiteScheduleInput $input,
    ): RedirectResponse {
        $admin = $this->currentAdmin();
        $website = $this->finder->find($code);
        $date = null;

        if (!$this->invalid($input)) {
            $this->attempt(function () use ($website, $input, $admin, &$date): void {
                $date = $this->schedules->schedule($website, $input->scheduledAt, $admin);
            });
        }

        if ($date instanceof DateTimeImmutable) {
            $this->addFlash('success', AdminWebsiteFlash::scheduled($date));
        }

        return $this->redirectToRoute('admin_website_show', ['code' => $code]);
    }

    #[Csrf('admin_website')]
    #[Route('/admin/versions/{code}/unschedule', name: 'admin_website_unschedule', methods: ['POST'])]
    public function unschedule(string $code): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->finder->find($code);
        $this->attempt(
            fn () => $this->versions->unschedule($website, $admin),
            AdminWebsiteFlash::UNSCHEDULED->text(),
        );

        return $this->redirectToRoute('admin_website_show', ['code' => $code]);
    }

    #[Csrf('admin_website')]
    #[Route('/admin/versions/{code}/publish', name: 'admin_website_publish', methods: ['POST'])]
    public function publish(string $code): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->finder->find($code);
        $this->attempt(
            fn () => $this->versions->publish($website, $admin),
            AdminWebsiteFlash::PUBLISHED->text($website->getCode()),
        );

        return $this->redirectToRoute('admin_website_show', ['code' => $code]);
    }

    #[Csrf('admin_website')]
    #[Route('/admin/versions/{code}/restore', name: 'admin_website_restore', methods: ['POST'])]
    public function restore(string $code): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->finder->find($code);
        $restored = null;
        $this->attempt(function () use ($website, $admin, &$restored): void {
            $restored = $this->versions->restore($website, $admin);
        }, AdminWebsiteFlash::RESTORED->text($website->getCode()));

        return $this->redirectToRoute('admin_website_show', ['code' => $restored?->getCode() ?? $code]);
    }

    #[Csrf('admin_website')]
    #[Route('/admin/versions/{code}/delete', name: 'admin_website_delete', methods: ['POST'])]
    public function delete(string $code): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $website = $this->finder->find($code);
        $deleted = $this->attempt(
            fn () => $this->versions->delete($website, $admin),
            AdminWebsiteFlash::DELETED->text($code),
        );

        if ($deleted) {
            return $this->redirectToRoute('admin_website_index');
        }

        return $this->redirectToRoute('admin_website_show', ['code' => $code]);
    }
}