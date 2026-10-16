<?php

namespace App\Establishment\Provider;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Repository\Establishment\EstablishmentSlotRepository;
use DateTimeImmutable;
use NeoPHP\Component\Routing\RoutingManagerInterface;

class ProWelcomeProvider
{
    public const RECOMMENDED_IMAGES = 4;

    private const EVER = '2000-01-01';

    public function __construct(
        private EstablishmentSlotRepository $slots,
        private RoutingManagerInterface $routing,
    ) {
    }

    public function build(CompanyEstablishment $establishment, User $user): array
    {
        $online = $establishment->getStatus() === EstablishmentStatus::ONLINE;
        $tasks = [
            $this->accountTask($user),
            $this->verifiedTask($online),
            $this->servicesTask($establishment),
            $this->imagesTask($establishment->getImages()->count()),
            $this->slotTask($establishment),
        ];
        $done = $this->doneCount($tasks);

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
            'shareUrl' => $this->shareUrl($establishment, $online),
            'publishUrl' => $this->routing->generate('pro_slot_new'),
        ];
    }

    private function accountTask(User $user): array
    {
        $done = $user->getEmailVerifiedAt() !== null;
        $text = 'Identité et e-mail vérifiés';

        return $this->task('account', $done, 'Créer votre compte', $text, 'pro_company', 'Voir');
    }

    private function verifiedTask(bool $online): array
    {
        $text = 'Vérification en cours par l’équipe Kréno';

        if ($online) {
            $text = 'SIRET contrôlé par l’équipe Kréno';
        }

        return $this->task(
            'verified',
            $online,
            'Établissement vérifié',
            $text,
            'pro_establishment',
            'Voir'
        );
    }

    private function servicesTask(CompanyEstablishment $establishment): array
    {
        $count = $this->activeServices($establishment);
        $title = 'Ajouter vos prestations';

        if ($count === 0) {
            $text = 'Ce que vous proposez, avec durée et prix';

            return $this->task('services', false, $title, $text, 'pro_services', 'Ajouter');
        }

        $discount = (int) $establishment->getDefaultDiscount();
        $text = sprintf('%d prestation · remise par défaut −%d %%', $count, $discount);

        if ($count > 1) {
            $text = sprintf('%d prestations · remise par défaut −%d %%', $count, $discount);
        }

        return $this->task('services', true, $title, $text, 'pro_services', 'Modifier');
    }

    private function imagesTask(int $images): array
    {
        $done = $images >= self::RECOMMENDED_IMAGES;
        $text = sprintf(
            'Les pages avec %d photos ou plus sont 2× plus réservées',
            self::RECOMMENDED_IMAGES
        );

        if ($done) {
            $text = sprintf('%d photos en ligne', $images);
        }

        return $this->task(
            'images',
            $done,
            'Ajouter des photos',
            $text,
            'pro_establishment',
            'Ajouter'
        );
    }

    private function slotTask(CompanyEstablishment $establishment): array
    {
        $published = $this->slots->countCreatedSince($establishment, new DateTimeImmutable(self::EVER)) > 0;
        $text = 'Un trou dans l’agenda ? C’est le moment.';

        if ($published) {
            $text = 'Votre premier créneau est publié';
        }

        return $this->task(
            'slot',
            $published,
            'Publier votre premier créneau',
            $text,
            'pro_slot_new',
            'Publier'
        );
    }

    private function activeServices(CompanyEstablishment $establishment): int
    {
        $count = 0;

        foreach ($establishment->getServices() as $service) {
            if ($service->isActive()) {
                ++$count;
            }
        }

        return $count;
    }

    private function doneCount(array $tasks): int
    {
        $done = 0;

        foreach ($tasks as $task) {
            if ($task['done']) {
                ++$done;
            }
        }

        return $done;
    }

    private function shareUrl(CompanyEstablishment $establishment, bool $online): ?string
    {
        $city = $establishment->getCity()?->getSlug();
        $slug = $establishment->getSlug();

        if (!$online || $city === null || $slug === null) {
            return null;
        }

        return $this->routing->generate('shop_show', ['city' => $city, 'slug' => $slug], true);
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