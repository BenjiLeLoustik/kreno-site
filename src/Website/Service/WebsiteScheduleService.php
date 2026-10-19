<?php

namespace App\Website\Service;

use App\Entity\User\User;
use App\Entity\Website\WebsiteConfig;
use App\Shared\Helper\DateHelper;
use App\Website\Exception\InvalidScheduleDateException;
use DateTimeImmutable;

class WebsiteScheduleService
{
    public function __construct(
        private WebsiteVersionService $versions,
    ) {
    }

    public function schedule(WebsiteConfig $website, ?string $scheduledAt, User $admin): DateTimeImmutable
    {
        $date = DateHelper::parse($scheduledAt);

        if ($date === null) {
            throw new InvalidScheduleDateException();
        }

        $this->versions->schedule($website, $date, $admin);

        return $date;
    }
}