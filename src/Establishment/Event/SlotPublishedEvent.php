<?php

namespace App\Establishment\Event;

use App\Entity\Establishment\EstablishmentSlot;

class SlotPublishedEvent
{
    public function __construct(
        private EstablishmentSlot $slot,
    ) {
    }

    public function getSlot(): EstablishmentSlot
    {
        return $this->slot;
    }
}