<?php

namespace App\Controller\Pro;

use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Establishment\EstablishmentImage;
use App\Establishment\Dto\ImageInput;
use App\Establishment\Dto\OrderInput;
use App\Establishment\Service\ImageService;
use App\Repository\Establishment\EstablishmentImageRepository;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Request\UploadedFile;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class ImageController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        private ImageService $imageService,
        private EstablishmentImageRepository $images,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/images', name: 'pro_image_upload', methods: ['POST'])]
    public function upload(Request $request): RedirectResponse
    {
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::ESTABLISHMENT_EDIT, $establishment);
        $file = $request->files->get('image');

        if (!$file instanceof UploadedFile) {
            $this->addFlash('error', 'Choisissez une photo à envoyer.');
        } else {
            $this->attempt(fn () => $this->imageService->upload($establishment, $file, $request->request->getString('alt')), 'Photo ajoutée.');
        }

        return $this->back();
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/images/{id}', name: 'pro_image_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(int $id, #[MapRequestPayload(validate: false)] ImageInput $input): RedirectResponse
    {
        $image = $this->findImage($id);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->imageService->updateAlt($image, $input->alt), 'Description de la photo enregistrée.');
        }

        return $this->back();
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/images/{id}/delete', name: 'pro_image_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id): RedirectResponse
    {
        $image = $this->findImage($id);
        $this->attempt(fn () => $this->imageService->delete($image), 'Photo supprimée.');

        return $this->back();
    }

    #[Csrf('pro')]
    #[Route('/pro/establishment/images/reorder', name: 'pro_image_reorder', methods: ['POST'])]
    public function reorder(#[MapRequestPayload(validate: false)] OrderInput $input): RedirectResponse
    {
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::ESTABLISHMENT_EDIT, $establishment);

        if (!$this->invalid($input)) {
            $this->attempt(fn () => $this->imageService->reorder($establishment, array_map('intval', $input->ids)), 'Ordre des photos enregistré.');
        }

        return $this->back();
    }

    private function back(): RedirectResponse
    {
        return $this->redirect($this->generateUrl('pro_establishment') . '#photos');
    }

    private function findImage(int $id): EstablishmentImage
    {
        $establishment = $this->currentEstablishment();
        $this->requirePermission(Permission::ESTABLISHMENT_EDIT, $establishment);

        return $this->images->findOneInEstablishment($establishment, $id) ?? throw $this->createNotFoundException('Photo introuvable.');
    }
}