<?php

namespace App\Establishment\Provider;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentImage;
use App\Establishment\Exception\ImageNotFoundException;
use App\Repository\Establishment\EstablishmentImageRepository;

class EstablishmentImageProvider
{
    public function __construct(
        private EstablishmentImageRepository $images,
    ) {
    }

    public function owned(CompanyEstablishment $establishment, int $id): EstablishmentImage
    {
        $image = $this->images->findOneInEstablishment($establishment, $id);

        if ($image === null) {
            throw new ImageNotFoundException();
        }

        return $image;
    }
}