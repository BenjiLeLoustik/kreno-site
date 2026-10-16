<?php

namespace App\Establishment\Model;

use App\Entity\City;

class SlotSearchResult
{
    public function __construct(
        private ?City $city,
        private array $categories,
        private array $matches,
        private ?float $latitude = null,
        private ?float $longitude = null,
    ) {
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getMatches(): array
    {
        return $this->matches;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function count(): int
    {
        return count($this->matches);
    }
}