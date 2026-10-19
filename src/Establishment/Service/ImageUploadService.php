<?php

namespace App\Establishment\Service;

use App\Entity\Company\CompanyEstablishment;
use App\Establishment\Exception\ImageRequiredException;
use NeoPHP\Component\Http\Request\UploadedFile;

class ImageUploadService
{
    public function __construct(
        private ImageService $images,
    ) {
    }

    public function upload(CompanyEstablishment $establishment, ?UploadedFile $file, string $alt): void
    {
        if ($file === null) {
            throw new ImageRequiredException();
        }

        $this->images->upload($establishment, $file, $alt);
    }
}