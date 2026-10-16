<?php

namespace App\Establishment\Presenter;

use App\Entity\Establishment\EstablishmentImage;
use NeoPHP\Component\Upload\UploadManagerInterface;

class ImagePresenter
{
    public function __construct(
        private UploadManagerInterface $uploads,
    ) {
    }

    public function summary(EstablishmentImage $image): array
    {
        return [
            'id' => $image->getId(),
            'url' => $this->uploads->url($image->getPath()),
            'width' => $image->getWidth(),
            'height' => $image->getHeight(),
            'alt' => $image->getAlt(),
            'position' => $image->getPosition(),
            'cover' => $image->getPosition() === 0,
        ];
    }

    public function summaries(array $images): array
    {
        return array_values(array_map($this->summary(...), $images));
    }

    public function url(?EstablishmentImage $image): ?string
    {
        if ($image === null) {
            return null;
        }

        return $this->uploads->url($image->getPath());
    }
}