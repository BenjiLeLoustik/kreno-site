<?php

namespace App\Booking\Service;

use App\Booking\Dto\BookingInput;
use App\Booking\Exception\GuestDetailsRequiredException;
use App\Entity\Establishment\EstablishmentSlot;
use App\Entity\User\User;
use App\Establishment\Exception\SlotNotFoundException;
use App\Repository\Establishment\EstablishmentSlotRepository;
use App\User\Service\GuestAccountService;

class GuestBookingService
{
    public function __construct(
        private EstablishmentSlotRepository $slots,
        private GuestAccountService $guests,
    ) {
    }

    public function slot(int $id): EstablishmentSlot
    {
        $slot = $this->slots->find($id);

        if (!$slot instanceof EstablishmentSlot) {
            throw new SlotNotFoundException();
        }

        return $slot;
    }

    public function guest(BookingInput $input): User
    {
        if ($input->email === null || $input->firstName === null || $input->phone === null) {
            throw new GuestDetailsRequiredException();
        }

        return $this->guests->create($input->email, $input->firstName, $input->phone);
    }
}