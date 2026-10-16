<?php

namespace App\Establishment\Service;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentImage;
use App\Repository\Establishment\EstablishmentImageRepository;
use App\Shared\Exception\BusinessRuleException;
use NeoPHP\Component\Http\Request\UploadedFile;
use NeoPHP\Component\Upload\Exception\UploadException;
use NeoPHP\Component\Upload\UploadManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class ImageService
{
    public const MAX_IMAGES = 6;

    public const MAX_SIZE = 8388608;

    public const MIME_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public function __construct(
        private OrmManagerInterface $orm,
        private UploadManagerInterface $uploads,
        private EstablishmentImageRepository $images,
    ) {
    }

    public function upload(CompanyEstablishment $establishment, ?UploadedFile $file, ?string $alt = null): EstablishmentImage
    {
        if ($file === null) {
            throw new BusinessRuleException('Choisissez une photo à ajouter.');
        }

        $count = $this->images->countForEstablishment($establishment);

        if ($count >= self::MAX_IMAGES) {
            throw new BusinessRuleException('Vous pouvez ajouter jusqu\'à {max} photos.', ['max' => self::MAX_IMAGES]);
        }

        try {
            $path = $this->uploads->store($file, 'establishments/' . $establishment->getId(), ['max_size' => self::MAX_SIZE, 'mime_types' => self::MIME_TYPES]);
        } catch (UploadException) {
            throw new BusinessRuleException('La photo doit être au format JPEG, PNG ou WebP et peser moins de 8 Mo.');
        }

        $absolutePath = $this->uploads->path($path);
        $size = getimagesize($absolutePath) ?: [0, 0, 'mime' => $file->getClientMimeType() ?? 'image/jpeg'];
        $alt = trim((string) $alt);

        $image = new EstablishmentImage();
        $image->setPath($path);
        $image->setWidth((int) $size[0]);
        $image->setHeight((int) $size[1]);
        $image->setMimeType((string) $size['mime']);
        $image->setSize((int) filesize($absolutePath));
        $image->setAlt($alt !== '' ? $alt : $establishment->getName());
        $image->setPosition($count);

        $establishment->addImage($image);
        $this->orm->persist($image);
        $this->orm->flush();

        return $image;
    }

    public function updateAlt(EstablishmentImage $image, ?string $alt): EstablishmentImage
    {
        $alt = trim((string) $alt);
        $image->setAlt($alt !== '' ? $alt : null);
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
            $this->reorder($establishment, array_map(static fn (EstablishmentImage $remaining): ?int => $remaining->getId(), $this->images->findForEstablishment($establishment)));
        }
    }

    public function reorder(CompanyEstablishment $establishment, array $ids): void
    {
        foreach (array_values($ids) as $position => $id) {
            $this->images->findOneInEstablishment($establishment, (int) $id)?->setPosition($position);
        }

        $this->orm->flush();
    }
}