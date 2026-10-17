<?php

namespace App\User\Service;

use App\Entity\User\User;
use App\User\Dto\SanctionInput;
use App\User\Exception\SelfSanctionException;
use App\Website\Provider\WebsiteProvider;

class AdminSanctionService
{
    public function __construct(
        private SanctionService $sanctions,
        private WebsiteProvider $website,
    ) {
    }

    public function days(SanctionInput $input): int
    {
        return $input->days ?? $this->website->getRule()->getSuspensionDays();
    }

    public function suspend(User $user, SanctionInput $input, User $admin): void
    {
        $this->assertNotSelf($user, $admin);
        $this->sanctions->suspend($user, $this->days($input), $input->reason, $admin);
    }

    public function ban(User $user, SanctionInput $input, User $admin): void
    {
        $this->assertNotSelf($user, $admin);
        $this->sanctions->ban($user, $input->reason, $admin);
    }

    private function assertNotSelf(User $user, User $admin): void
    {
        if ($user === $admin) {
            throw new SelfSanctionException();
        }
    }
}