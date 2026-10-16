<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use NeoPHP\Package\Orm\Mapping as ORM;

trait PositionTrait
{
    #[ORM\Column]
    private int $position = 0;

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }
}