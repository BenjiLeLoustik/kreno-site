<?php

namespace App\Establishment\Provider;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentFollower;
use App\Entity\User\User;
use App\Establishment\Exception\EstablishmentNotFoundException;
use App\Establishment\Presenter\EstablishmentPagePresenter;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Repository\Establishment\EstablishmentFollowerRepository;
use DateTimeImmutable;

class FollowPageProvider
{
    public function __construct(
        private EstablishmentFollowerRepository $followers,
        private CompanyEstablishmentRepository $establishments,
        private EstablishmentPagePresenter $presenter,
    ) {
    }

    public function index(User $user): array
    {
        $now = new DateTimeImmutable();
        $follows = [];

        foreach ($this->followers->findByUser($user) as $follower) {
            if ($follower instanceof EstablishmentFollower && $follower->getEstablishment() !== null) {
                $follows[] = $this->presenter->card($follower->getEstablishment(), $now);
            }
        }

        return [
            'follows' => $follows
        ];
    }

    public function establishment(int $id): CompanyEstablishment
    {
        $establishment = $this->establishments->find($id);

        if (!$establishment instanceof CompanyEstablishment) {
            throw new EstablishmentNotFoundException();
        }

        return $establishment;
    }
}