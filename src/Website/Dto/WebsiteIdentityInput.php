<?php

namespace App\Website\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class WebsiteIdentityInput
{
    #[Assert\Length(min: 2, max: 120)]
    public ?string $name = null;

    public ?string $description = null;

    #[Assert\Length(min: 2, max: 80)]
    public ?string $siteName = null;

    #[Assert\Length(min: 3, max: 120)]
    public ?string $domain = null;

    #[Assert\Length(min: 3, max: 255)]
    public ?string $tagline = null;

    #[Assert\Length(min: 3)]
    public ?string $siteDescription = null;

    #[Assert\Length(max: 160)]
    public ?string $seoTitle = null;

    #[Assert\Email]
    public ?string $contactEmail = null;
}