<?php

namespace App\Establishment\Service;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentImage;
use App\Establishment\Exception\ImageLimitReachedException;
use App\Establishment\Exception\ImageRequiredException;
use App\Establishment\Exception\InvalidImageException;
use App\Repository\Establishment\EstablishmentImageRepository;
use NeoPHP\Component\Http\Request\UploadedFile;
use NeoPHP\Component\Upload\Exception\UploadException;
use NeoPHP\Component\Upload\UploadManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class ImageService
{
    public const MAX_IMAGES = 6;

    public const MAX_SIZE = 8388608;

    public const MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    public const DEFAULT_MIME_TYPE = 'image/jpeg';

    public function __construct(
        private OrmManagerInterface $orm,
        private UploadManagerInterface $uploads,
        private EstablishmentImageRepository $images,
    ) {
    }

    public function upload(
        CompanyEstablishment $establishment,
        ?UploadedFile $file,
        ?string $alt = null,
    ): EstablishmentImage {
        if ($file === null) {
            throw new ImageRequiredException();
        }

        $count = $this->images->countForEstablishment($establishment);

        if ($count >= self::MAX_IMAGES) {
            throw new ImageLimitReachedException(self::MAX_IMAGES);
        }

        $path = $this->store($establishment, $file);
        $absolutePath = $this->uploads->path($path);
        $size = $this->imageSize($absolutePath, $file);

        $image = new EstablishmentImage();
        $image->setPath($path);
        $image->setWidth((int) $size[0]);
        $image->setHeight((int) $size[1]);
        $image->setMimeType((string) $size['mime']);
        $image->setSize((int) filesize($absolutePath));
        $image->setAlt($this->text($alt) ?? $establishment->getName());
        $image->setPosition($count);

        $establishment->addImage($image);
        $this->orm->persist($image);
        $this->orm->flush();

        return $image;
    }

    public function updateAlt(EstablishmentImage $image, ?string $alt): EstablishmentImage
    {
        $image->setAlt($this->text($alt));
        $this->orm->flush();

        return $image;
    }

    public function delete(EstablishmentImage $image): void
    {
        $establishment = $image->getEstablishment();
        $path = $image->getPath();

        $establishment?->removeImage($image);
        $this->orm->remove($image);
        $this->orm->flush();
        $this->uploads->delete($path);

        if ($establishment !== null) {
            $this->reorder($establishment, $this->remainingIds($establishment));
        }
    }

    public function reorder(CompanyEstablishment $establishment, array $ids): void
    {
        foreach (array_values($ids) as $position => $id) {
            $this->images->findOneInEstablishment($establishment, (int) $id)?->setPosition($position);
        }

        $this->orm->flush();
    }

    private function store(CompanyEstablishment $establishment, UploadedFile $file): string
    {
        $directory = 'establishments/' . $establishment->getId();

        try {
            return $this->uploads->store($file, $directory, [
                'max_size' => self::MAX_SIZE,
                'mime_types' => self::MIME_TYPES,
            ]);
        } catch (UploadException) {
            throw new InvalidImageException();
        }
    }

    private function imageSize(string $absolutePath, UploadedFile $file): array
    {
        $size = getimagesize($absolutePath);

        if ($size === false) {
            return [0, 0, 'mime' => $file->getClientMimeType() ?? self::DEFAULT_MIME_TYPE];
        }

        return $size;
    }

    private function remainingIds(CompanyEstablishment $establishment): array
    {
        $ids = [];

        foreach ($this->images->findForEstablishment($establishment) as $image) {
            $ids[] = $image->getId();
        }

        return $ids;
    }

    private function text(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $value;
    }
}