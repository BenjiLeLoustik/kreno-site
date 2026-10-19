<?php

namespace App\User\Provider;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\User\Exception\StaffMemberNotFoundException;
use App\User\Helper\StaffRoleHelper;
use App\User\Presenter\StaffPresenter;
use App\User\Service\StaffService;

class AdminTeamPageProvider
{
    public function __construct(
        private StaffService $staff,
        private UserRepository $users,
        private StaffPresenter $presenter,
    ) {
    }

    public function index(User $admin): array
    {
        return [
            'members' => $this->presenter->presentAll($this->staff->members(), $admin),
            'roles' => StaffRoleHelper::choices(),
        ];
    }

    public function member(int $id): User
    {
        $user = $this->users->find($id);

        if (!$user instanceof User) {
            throw new StaffMemberNotFoundException();
        }

        return $user;
    }
}