<?php

namespace App\Company\Model;

class RegistryEstablishment
{
    public function __construct(
        public string $siret,
        public string $siren,
        public string $legalName,
        public ?string $brandName,
        public ?string $address,
        public ?string $postalCode,
        public ?string $locality,
        public ?string $apeCode,
        public ?string $apeLabel,
        public bool $active,
        public ?float $latitude,
        public ?float $longitude,
    ) {
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}