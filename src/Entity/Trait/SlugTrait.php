<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use NeoPHP\Package\Orm\Mapping as ORM;

trait SlugTrait
{
    #[ORM\Column(length: 140)]
    private ?string $slug = null;

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }
}