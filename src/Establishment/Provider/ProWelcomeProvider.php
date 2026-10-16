<?php

namespace App\Establishment\Provider;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentService;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Repository\Establishment\EstablishmentSlotRepository;
use DateTimeImmutable;
use NeoPHP\Component\Routing\RoutingManagerInterface;

class ProWelcomeProvider
{
    public const RECOMMENDED_IMAGES = 4;

    public function __construct(
        private EstablishmentSlotRepository $slots,
        private RoutingManagerInterface $routing,
    ) {
    }

    public function build(CompanyEstablishment $establishment, User $user): array
    {
        $services = array_filter($establishment->getServices()->toArray(), static fn (EstablishmentService $service): bool => $service->isActive());
        $images = $establishment->getImages()->count();
        $published = $this->slots->countCreatedSince($establishment, new DateTimeImmutable('2000-01-01')) > 0;
        $online = $establishment->getStatus() === EstablishmentStatus::ONLINE;

        $tasks = [
            $this->task('account', $user->getEmailVerifiedAt() !== null, 'Créer votre compte', 'Identité et e-mail vérifiés', 'pro_company', 'Voir'),
            $this->task('verified', $online, 'Établissement vérifié', $online ? 'SIRET contrôlé par l’équipe Kréno' : 'Vérification en cours par l’équipe Kréno', 'pro_establishment', 'Voir'),
            $this->task('services', $services !== [], 'Ajouter vos prestations', $services !== [] ? sprintf('%d prestation%s · remise par défaut −%d %%', count($services), count($services) > 1 ? 's' : '', (int) $establishment->getDefaultDiscount()) : 'Ce que vous proposez, avec durée et prix', 'pro_services', $services !== [] ? 'Modifier' : 'Ajouter'),
            $this->task('images', $images >= self::RECOMMENDED_IMAGES, 'Ajouter des photos', $images >= self::RECOMMENDED_IMAGES ? sprintf('%d photos en ligne', $images) : sprintf('Les pages avec %d photos ou plus sont 2× plus réservées', self::RECOMMENDED_IMAGES), 'pro_establishment', 'Ajouter'),
            $this->task('slot', $published, 'Publier votre premier créneau', $published ? 'Votre premier créneau est publié' : 'Un trou dans l’agenda ? C’est le moment.', 'pro_slot_new', 'Publier'),
        ];

        $done = count(array_filter($tasks, static fn (array $task): bool => $task['done']));
        $city = $establishment->getCity()?->getSlug();
        $slug = $establishment->getSlug();

        return [
            'firstName' => $user->getFirstName(),
            'establishment' => [
                'name' => $establishment->getName(),
                'city' => $establishment->getCity()?->getName(),
                'online' => $online,
            ],
            'tasks' => $tasks,
            'done' => $done,
            'total' => count($tasks),
            'progress' => (int) round($done / count($tasks) * 100),
            'completed' => $done === count($tasks),
            'shareUrl' => $online && $city !== null && $slug !== null ? $this->routing->generate('shop_show', ['city' => $city, 'slug' => $slug], true) : null,
            'publishUrl' => $this->routing->generate('pro_slot_new'),
        ];
    }

    private function task(string $key, bool $done, string $title, string $text, string $route, string $cta): array
    {
        return [
            'key' => $key,
            'done' => $done,
            'title' => $title,
            'text' => $text,
            'url' => $this->routing->generate($route),
            'cta' => $cta,
        ];
    }
}