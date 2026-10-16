<?php

namespace App\Controller\Admin;

use App\Booking\Enum\BookingStatus;
use App\Entity\User\User;
use App\Repository\BookingRepository;
use App\Repository\User\UserRepository;
use App\User\Dto\SanctionInput;
use App\User\Enum\UserRole;
use App\User\Enum\UserStatus;
use App\User\Presenter\UserPresenter;
use App\User\Service\SanctionService;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class UserController extends AbstractAdminController
{
    private const PER_PAGE = 50;

    public function __construct(
        private UserRepository $users,
        private BookingRepository $bookings,
        private SanctionService $sanctions,
        private WebsiteProvider $website,
        private UserPresenter $presenter,
    ) {
    }

    #[Route('/admin/users', name: 'admin_user_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);

        return $this->page($request, null);
    }

    #[Route('/admin/users/{id}', name: 'admin_user_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Request $request, int $id): Response
    {
        $this->currentAdmin(UserRole::MODERATOR);

        return $this->page($request, $this->findUser($id));
    }

    #[Csrf('admin_user')]
    #[Route('/admin/users/{id}/suspend', name: 'admin_user_suspend', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function suspend(int $id, #[MapRequestPayload(validate: false)] SanctionInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $user = $this->findUser($id);

        if (!$this->isSelf($user, $admin) && !$this->invalid($input)) {
            $days = $input->days ?? $this->website->getRule()->getSuspensionDays();
            $this->attempt(fn () => $this->sanctions->suspend($user, $days, $input->reason, $admin), sprintf('Compte suspendu %d jours. L\'utilisateur a été prévenu par e-mail.', $days));
        }

        return $this->redirectToRoute('admin_user_show', ['id' => $id]);
    }

    #[Csrf('admin_user')]
    #[Route('/admin/users/{id}/lift', name: 'admin_user_lift', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function lift(int $id): RedirectResponse
    {
        $admin = $this->currentAdmin(UserRole::MODERATOR);
        $user = $this->findUser($id);
        $this->attempt(fn () => $this->sanctions->lift($user, $admin), 'Compte réactivé.');

        return $this->redirectToRoute('admin_user_show', ['id' => $id]);
    }

    #[Csrf('admin_user')]
    #[Route('/admin/users/{id}/ban', name: 'admin_user_ban', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function ban(int $id, #[MapRequestPayload(validate: false)] SanctionInput $input): RedirectResponse
    {
        $admin = $this->currentAdmin();
        $user = $this->findUser($id);

        if (!$this->isSelf($user, $admin) && !$this->invalid($input)) {
            $this->attempt(fn () => $this->sanctions->ban($user, $input->reason, $admin), 'Compte banni.');
        }

        return $this->redirectToRoute('admin_user_show', ['id' => $id]);
    }

    private function page(Request $request, ?User $selected): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $status = UserStatus::tryFrom($request->query->getString('status'));
        $search = trim($request->query->getString('q'));
        $users = $this->users->search($search !== '' ? $search : null, $status, self::PER_PAGE + 1, ($page - 1) * self::PER_PAGE);
        $hasNext = count($users) > self::PER_PAGE;
        $users = array_slice($users, 0, self::PER_PAGE);
        $selected ??= $users[0] ?? null;

        return $this->render('admin/user/index.html.twig', [
            'filters' => ['q' => $search, 'status' => $status?->value, 'page' => $page],
            'hasNext' => $hasNext,
            'statuses' => array_map(static fn (UserStatus $case): array => ['value' => $case->value, 'label' => $case->label()], UserStatus::cases()),
            'users' => array_map(fn (User $user): array => $this->presenter->admin($user), $users),
            'selected' => $selected !== null ? $this->presenter->admin($selected, $this->stats($selected)) : null,
            'suspensionDays' => $this->website->getRule()->getSuspensionDays(),
        ]);
    }

    private function stats(User $user): array
    {
        $rule = $this->website->getRule();

        return [
            'bookings' => $this->bookings->countByCustomer($user),
            'honored' => $this->bookings->countByCustomer($user, BookingStatus::HONORED),
            'cancelled' => $this->bookings->countByCustomer($user, BookingStatus::CANCELLED_BY_CUSTOMER),
            'noShows' => $this->bookings->countByCustomer($user, BookingStatus::NO_SHOW),
            'recentNoShows' => $this->bookings->countNoShowsSince($user, new DateTimeImmutable(sprintf('-%d days', $rule->getNoShowWindowDays()))),
            'toleratedNoShows' => $rule->getToleratedNoShows(),
            'noShowWindowDays' => $rule->getNoShowWindowDays(),
        ];
    }

    private function findUser(int $id): User
    {
        $user = $this->users->find($id);

        return $user instanceof User ? $user : throw $this->createNotFoundException('Utilisateur introuvable.');
    }

    private function isSelf(User $user, User $admin): bool
    {
        if ($user !== $admin) {
            return false;
        }

        $this->addFlash('error', 'Vous ne pouvez pas vous sanctionner vous-même.');

        return true;
    }
}