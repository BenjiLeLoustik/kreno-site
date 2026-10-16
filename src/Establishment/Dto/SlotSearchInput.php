<?php

namespace App\Establishment\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class SlotSearchInput
{
    #[Assert\Regex(pattern: '/^[a-z0-9-]*$/')]
    public ?string $where = null;

    #[Assert\Regex(pattern: '/^[a-z0-9-]*$/')]
    public ?string $what = null;

    #[Assert\All([new Assert\Regex(pattern: '/^[a-z0-9-]+$/')])]
    public array $categories = [];

    #[Assert\Choice(choices: ['now', 'morning', 'afternoon', 'evening', 'tomorrow', 'week'])]
    public string $when = 'now';

    #[Assert\Range(min: 0.5, max: 50)]
    public ?float $distance = null;

    #[Assert\Range(min: -90, max: 90)]
    public ?float $latitude = null;

    #[Assert\Range(min: -180, max: 180)]
    public ?float $longitude = null;

    #[Assert\Choice(choices: ['soonest', 'nearest', 'discount'])]
    public string $sort = 'soonest';

    #[Assert\Range(min: 1, max: 100)]
    public int $limit = 50;
}