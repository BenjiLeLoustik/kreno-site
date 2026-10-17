<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Establishment\Dto\ImageInput;
use App\Establishment\Dto\OrderInput;
use App\Establishment\Enum\ImageFlash;
use App\Establishment\Mapper\ImageRequestMapper;
use App\Establishment\Mapper\OrderRequestMapper;
use App\Establishment\Provider\EstablishmentImageProvider;
use App\Establishment\Service\ImageService;
use App\Establishment\Service\ImageUploadService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class ImageController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private ImageService $imageService,
        private ImageUploadService $uploads,
        private EstablishmentImageProvider $images,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/images', name: 'pro_image_upload', methods: ['POST'])]
    public function upload(Request $request): RedirectResponse
    {
        $establishment = $this->permittedEstablishment(Permission::ESTABLISHMENT_EDIT);
        $file = ImageRequestMapper::file($request);
        $alt = ImageRequestMapper::alt($request);
        $this->attempt(fn () => $this->uploads->upload($establishment, $file, $alt), ImageFlash::ADDED->text());

        return $this->redirect($this->generateUrl('pro_establishment') . '#photos');
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/establishment/images/{id}',
        name: 'pro_image_update',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function update(int $id, #[MapRequestPayload(validate: false)] ImageInput $input): RedirectResponse
    {
        $image = $this->images->owned($this->permittedEstablishment(Permission::ESTABLISHMENT_EDIT), $id);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->imageService->updateAlt($image, $input->alt), ImageFlash::ALT_SAVED->text());
        }

        return $this->redirect($this->generateUrl('pro_establishment') . '#photos');
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/establishment/images/{id}/delete',
        name: 'pro_image_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function delete(int $id): RedirectResponse
    {
        $image = $this->images->owned($this->permittedEstablishment(Permission::ESTABLISHMENT_EDIT), $id);
        $this->attempt(fn () => $this->imageService->delete($image), ImageFlash::DELETED->text());

        return $this->redirect($this->generateUrl('pro_establishment') . '#photos');
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/images/reorder', name: 'pro_image_reorder', methods: ['POST'])]
    public function reorder(#[MapRequestPayload(validate: false)] OrderInput $input): RedirectResponse
    {
        $establishment = $this->permittedEstablishment(Permission::ESTABLISHMENT_EDIT);

        if (!$this->invalid($input)) {
            $this->attempt(
                fn () => $this->imageService->reorder($establishment, OrderRequestMapper::ids($input)),
                ImageFlash::REORDERED->text(),
            );
        }

        return $this->redirect($this->generateUrl('pro_establishment') . '#photos');
    }
}