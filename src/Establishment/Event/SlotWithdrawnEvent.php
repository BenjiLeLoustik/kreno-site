<?php

namespace App\Establishment\Event;

use App\Entity\Establishment\EstablishmentSlot;
use App\Entity\User\User;

class SlotWithdrawnEvent
{
    public function __construct(
        private EstablishmentSlot $slot,
        private ?User $actor = null,
    ) {
    }

    public function getSlot(): EstablishmentSlot
    {
        return $this->slot;
    }

    public function getActor(): ?User
    {
        return $this->actor;
    }
}