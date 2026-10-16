<?php

namespace App\Controller\Front;

use App\Catalog\Provider\NavigationProvider;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentSlot;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Enum\SlotStatus;
use App\Establishment\Helper\GeoHelper;
use App\Establishment\Presenter\EstablishmentPagePresenter;
use App\Establishment\Presenter\SlotPresenter;
use App\Establishment\Service\FollowService;
use App\Repository\CityRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Repository\Establishment\EstablishmentFollowerRepository;
use App\Repository\Establishment\EstablishmentSlotRepository;
use DateTimeImmutable;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\JsonResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Session\SessionManagerInterface;

class ShopController extends AbstractFrontController
{
    public const OLD_BOOKING = 'app.booking.old';

    public function __construct(
        private CityRepository $cities,
        private CompanyEstablishmentRepository $establishments,
        private EstablishmentSlotRepository $slots,
        private EstablishmentFollowerRepository $followers,
        private FollowService $follows,
        private NavigationProvider $navigation,
        private EstablishmentPagePresenter $pagePresenter,
        private SlotPresenter $slotPresenter,
        private SessionManagerInterface $session,
    ) {
    }

    #[Route('/shop/{city}/{slug}', name: 'shop_show', methods: ['GET'], requirements: ['city' => '[a-z0-9-]+', 'slug' => '[a-z0-9-]+'])]
    public function show(Request $request, string $city, string $slug): Response
    {
        $establishment = $this->findEstablishment($city, $slug);
        [$latitude, $longitude] = $this->origin($request);

        return $this->render('front/shop/show.html.twig', [
            'breadcrumb' => $this->breadcrumb($establishment),
            'establishment' => $this->pagePresenter->publicPage($establishment, $this->optionalUser(), $latitude, $longitude),
        ]);
    }

    #[Route('/shop/{city}/{slug}/follow', name: 'shop_follow', methods: ['POST'], requirements: ['city' => '[a-z0-9-]+', 'slug' => '[a-z0-9-]+'])]
    public function follow(string $city, string $slug): JsonResponse
    {
        $user = $this->currentUser();
        $establishment = $this->findEstablishment($city, $slug);
        $following = $this->follows->toggle($establishment, $user);

        return $this->json([
            'following' => $following,
            'label' => $following ? 'Salon suivi ✓' : 'Suivre ce salon',
            'followersCount' => $this->followers->countForEstablishment($establishment),
        ]);
    }

    #[Route('/slot/{id}', name: 'slot_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function slot(Request $request, int $id): Response
    {
        $slot = $this->slots->find($id);
        $establishment = $slot instanceof EstablishmentSlot ? $slot->getEstablishment() : null;

        if ($establishment === null || $establishment->getStatus() !== EstablishmentStatus::ONLINE || $slot->getStatus() === SlotStatus::WITHDRAWN) {
            throw $this->createNotFoundException('Ce créneau n\'est plus disponible.');
        }

        $now = new DateTimeImmutable();
        [$latitude, $longitude] = $this->origin($request);
        $distance = $latitude !== null && $longitude !== null ? GeoHelper::between((string) $latitude, (string) $longitude, $establishment->getLatitude(), $establishment->getLongitude()) : null;
        $others = array_filter(
            $this->slots->findAvailableForEstablishment($establishment, $now, 20),
            static fn (EstablishmentSlot $other): bool => $other !== $slot && $other->getStartsAt()?->format('Y-m-d') === $slot->getStartsAt()?->format('Y-m-d'),
        );
        $user = $this->optionalUser();
        $old = $this->session->get(self::OLD_BOOKING);
        $this->session->remove(self::OLD_BOOKING);
        $old = is_array($old) && ($old['slot'] ?? null) === $id ? $old['values'] : [];

        return $this->render('front/slot/show.html.twig', [
            'breadcrumb' => $this->breadcrumb($establishment),
            'slot' => $this->slotPresenter->detail($slot, $distance, $now),
            'others' => $this->slotPresenter->cards(array_values($others), $now),
            'user' => $user !== null ? ['firstName' => $user->getFirstName(), 'email' => $user->getEmail()] : null,
            'form' => $old + ['firstName' => $user?->getFirstName(), 'email' => $user?->getEmail(), 'phone' => $user?->getPhone(), 'note' => null],
        ]);
    }

    private function findEstablishment(string $city, string $slug): CompanyEstablishment
    {
        $cityEntity = $this->cities->findOneBySlug($city) ?? throw $this->createNotFoundException('Ville introuvable.');

        return $this->establishments->findOnePublic($cityEntity, $slug) ?? throw $this->createNotFoundException('Cet établissement n\'existe pas ou n\'est plus en ligne.');
    }

    private function origin(Request $request): array
    {
        $latitude = $request->query->get('latitude');
        $longitude = $request->query->get('longitude');

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return [null, null];
        }

        return [max(-90.0, min(90.0, (float) $latitude)), max(-180.0, min(180.0, (float) $longitude))];
    }

    private function breadcrumb(CompanyEstablishment $establishment): array
    {
        $city = $establishment->getCity();
        $category = $establishment->getCategory();
        $items = [['label' => 'Accueil', 'route' => 'home_index', 'parameters' => []]];

        if ($city !== null) {
            $items[] = ['label' => $city->getName(), 'route' => 'city_show', 'parameters' => ['slug' => $city->getSlug()]];
        }

        if ($city !== null && $category !== null) {
            $items[] = ['label' => $category->getName(), 'route' => 'city_category_show', 'parameters' => ['city' => $city->getSlug(), 'category' => $category->getSlug()]];
        }

        $items[] = ['label' => $establishment->getName(), 'route' => null, 'parameters' => []];

        return $items;
    }
}