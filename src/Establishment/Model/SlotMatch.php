<?php

namespace App\Establishment\Model;

use App\Entity\Establishment\EstablishmentSlot;

class SlotMatch
{
    public function __construct(
        private EstablishmentSlot $slot,
        private ?float $distance = null,
    ) {
    }

    public function getSlot(): EstablishmentSlot
    {
        return $this->slot;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }
}